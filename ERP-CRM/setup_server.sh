#!/usr/bin/env bash
# Thiết lập môi trường Laravel 10 trên Ubuntu 22.04.
# Chạy tại thư mục gốc dự án: sudo bash setup_server.sh

set -Eeuo pipefail

# ========================= CẤU HÌNH DỄ THAY ĐỔI =========================
# Để trống để script hỏi trực tiếp khi chạy; hoặc điền sẵn để chạy không cần nhập.
DOMAIN=""
UPLOAD_LIMIT="500M"
PHP_VERSION="8.3"
# Đổi thành cổng còn trống (ví dụ: 8080) nếu server đã có website dùng cổng 80.
NGINX_PORT="80"
# Bật SSL tự động bằng Certbot. Đổi thành "false" nếu chưa sẵn sàng trỏ DNS.
ENABLE_SSL="true"
# Để trống để script hỏi trực tiếp khi ENABLE_SSL="true".
CERTBOT_EMAIL=""
# Các tên miền bổ sung, cách nhau bằng dấu cách (ví dụ: "www.example.com").
# Để trống nếu các tên miền này chưa trỏ DNS về server.
DOMAIN_ALIASES=""
# Kiểm tra IP public và DNS trước khi cấu hình web/SSL (chỉ cảnh báo, không dừng script).
CHECK_PUBLIC_IP="true"
# ========================================================================

# Ưu tiên thư mục đang chạy. Nếu repository có lớp thư mục ERP-CRM bên ngoài
# (ví dụ: .../ERP-CRM/ERP-CRM), tự động dùng thư mục Laravel nằm bên trong.
CURRENT_DIR="$(pwd -P)"
if [[ -f "${CURRENT_DIR}/artisan" && -f "${CURRENT_DIR}/composer.json" ]]; then
    PROJECT_DIR="${CURRENT_DIR}"
elif [[ -f "${CURRENT_DIR}/ERP-CRM/artisan" && -f "${CURRENT_DIR}/ERP-CRM/composer.json" ]]; then
    PROJECT_DIR="${CURRENT_DIR}/ERP-CRM"
else
    PROJECT_DIR="${CURRENT_DIR}"
fi
PROJECT_NAME="$(basename "${PROJECT_DIR}")"
PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
NGINX_SITE=""
BACKUP_SCRIPT="/root/auto_backup.sh"

# Màu sắc cho thông báo tiến trình.
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC} $*"; }
warning() { echo -e "${YELLOW}[CẢNH BÁO]${NC} $*"; }
error()   { echo -e "${RED}[LỖI]${NC} $*" >&2; }

is_valid_domain() {
    [[ "$1" =~ ^([[:alnum:]]([[:alnum:]-]{0,61}[[:alnum:]])?\.)+[[:alpha:]]{2,}$ ]]
}

is_valid_email() {
    [[ "$1" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]]
}

# Hỏi thông tin bắt buộc khi chưa điền ở phần cấu hình đầu file.
# Việc này phù hợp khi chạy thủ công bằng "sudo bash setup_server.sh".
prompt_configuration() {
    if [[ -z "${DOMAIN}" ]]; then
        if [[ ! -t 0 ]]; then
            error "DOMAIN đang để trống. Hãy điền ở đầu file hoặc chạy script từ terminal tương tác."
            exit 1
        fi
        while true; do
            read -r -p "Nhập tên miền (ví dụ: crm.congty.com): " DOMAIN
            DOMAIN="${DOMAIN,,}"
            if is_valid_domain "${DOMAIN}"; then
                break
            fi
            warning "Tên miền chưa hợp lệ. Chỉ nhập tên miền, không thêm http://, https:// hoặc đường dẫn."
        done
    fi

    if [[ "${ENABLE_SSL}" == "true" && -z "${CERTBOT_EMAIL}" ]]; then
        if [[ ! -t 0 ]]; then
            error "CERTBOT_EMAIL đang để trống. Hãy điền ở đầu file hoặc chạy script từ terminal tương tác."
            exit 1
        fi
        while true; do
            read -r -p "Nhập email nhận thông báo gia hạn SSL: " CERTBOT_EMAIL
            if is_valid_email "${CERTBOT_EMAIL}"; then
                break
            fi
            warning "Email chưa hợp lệ. Vui lòng nhập lại."
        done
    fi
}

# Lấy IPv4 public qua hai dịch vụ dự phòng. Không in lỗi mạng ra màn hình.
get_public_ipv4() {
    local ip service_url
    for service_url in "https://api.ipify.org" "https://icanhazip.com"; do
        ip="$(curl -4 -fsS --max-time 10 "${service_url}" 2>/dev/null || true)"
        if [[ "${ip}" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
            printf '%s\n' "${ip}"
            return 0
        fi
    done
    return 1
}

# Đối chiếu IP public thấy từ server với IP A hiện tại của DOMAIN.
# Kết quả chỉ để tham khảo: trên Fortinet, IP outbound có thể khác public VIP inbound.
check_public_ip_and_dns() {
    local public_ip dns_ips

    [[ "${CHECK_PUBLIC_IP}" == "true" ]] || return 0

    public_ip="$(get_public_ipv4 || true)"
    if [[ -n "${public_ip}" ]]; then
        info "IPv4 public server đang thấy từ Internet: ${public_ip}"
    else
        warning "Không lấy được IPv4 public (có thể server không ra được Internet hoặc bị chặn dịch vụ kiểm tra)."
    fi

    if [[ "${DOMAIN}" == "example.com" ]]; then
        warning "DOMAIN vẫn là example.com; bỏ qua đối chiếu DNS."
        return 0
    fi

    dns_ips="$(getent ahostsv4 "${DOMAIN}" 2>/dev/null | awk '!seen[$1]++ {result = result (result ? ", " : "") $1} END {print result}' || true)"
    if [[ -z "${dns_ips}" ]]; then
        warning "DOMAIN ${DOMAIN} chưa phân giải được IPv4. Hãy tạo bản ghi DNS A trước khi bật Certbot."
    else
        info "IPv4 DNS hiện tại của ${DOMAIN}: ${dns_ips}"
        if [[ -n "${public_ip}" && ",${dns_ips// /}," != *",${public_ip},"* ]]; then
            warning "IP DNS không trùng IP public vừa kiểm tra. Với Fortinet/VIP đây có thể là bình thường; hãy xác nhận NAT port 80/443."
        fi
    fi
}

# Hiển thị vị trí lỗi nếu có lệnh thất bại.
trap 'error "Script dừng ở dòng $LINENO."' ERR

# Script cài gói và ghi cấu hình hệ thống nên bắt buộc chạy bằng root.
if [[ "${EUID}" -ne 0 ]]; then
    error "Vui lòng chạy: sudo bash setup_server.sh"
    exit 1
fi

prompt_configuration

if ! is_valid_domain "${DOMAIN}"; then
    error "DOMAIN không hợp lệ: ${DOMAIN}"
    exit 1
fi
NGINX_SITE="/etc/nginx/sites-available/${DOMAIN}"

# Kiểm tra cấu trúc tối thiểu của một dự án Laravel.
if [[ ! -f "${PROJECT_DIR}/artisan" || ! -f "${PROJECT_DIR}/composer.json" ]]; then
    error "Không tìm thấy Laravel tại ${CURRENT_DIR} hoặc ${CURRENT_DIR}/ERP-CRM"
    exit 1
fi

if [[ ! "${PHP_VERSION}" =~ ^[0-9]+\.[0-9]+$ ]]; then
    error "PHP_VERSION phải có định dạng x.y, ví dụ: 8.3"
    exit 1
fi

if [[ ! "${NGINX_PORT}" =~ ^[0-9]+$ ]] || (( NGINX_PORT < 1 || NGINX_PORT > 65535 )); then
    error "NGINX_PORT phải là số trong khoảng 1-65535, ví dụ: 8080"
    exit 1
fi

if [[ "${ENABLE_SSL}" != "true" && "${ENABLE_SSL}" != "false" ]]; then
    error "ENABLE_SSL chỉ nhận giá trị true hoặc false"
    exit 1
fi

# Let's Encrypt xác thực HTTP-01 qua port 80. Certbot --nginx sẽ tự thêm
# server block SSL ở port 443 và redirect HTTP sang HTTPS sau khi cấp cert.
if [[ "${ENABLE_SSL}" == "true" ]]; then
    if [[ "${NGINX_PORT}" != "80" ]]; then
        error "Certbot tự động cần NGINX_PORT=80 để xác thực domain."
        error "Nếu port 80 do Nginx dùng cho web khác, vẫn để 80: Nginx phân biệt bằng server_name."
        error "Nếu port 80 thuộc dịch vụ khác, đặt ENABLE_SSL=false và dùng DNS challenge riêng."
        exit 1
    fi
    if ! is_valid_email "${CERTBOT_EMAIL}"; then
        error "Hãy đặt CERTBOT_EMAIL là email thật trước khi bật SSL tự động."
        exit 1
    fi
fi

info "Bắt đầu thiết lập dự án: ${PROJECT_DIR}"
info "Domain: ${DOMAIN} | Cổng Nginx: ${NGINX_PORT} | PHP: ${PHP_VERSION} | Giới hạn upload: ${UPLOAD_LIMIT}"

# Cập nhật danh sách gói và cài công cụ cần để thêm PPA.
info "Cập nhật hệ thống và cài công cụ quản lý repository..."
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y software-properties-common ca-certificates

# PPA Ondrej cung cấp PHP 8.3 cho Ubuntu 22.04.
info "Thêm repository ppa:ondrej/php..."
add-apt-repository -y ppa:ondrej/php
apt-get update

# Cài web server, công cụ hỗ trợ và PHP cùng extension Laravel cần thiết.
info "Cài Nginx, Composer, Rclone, công cụ mount NAS và PHP ${PHP_VERSION}..."
apt-get install -y \
    nginx composer rclone cifs-utils unzip zip curl certbot python3-certbot-nginx \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-gd"
success "Đã cài đặt các gói cần thiết."

info "Kiểm tra IP public và DNS..."
check_public_ip_and_dns

# Tăng giới hạn upload ở cấu hình PHP-FPM.
if [[ ! -f "${PHP_INI}" ]]; then
    error "Không tìm thấy php.ini của PHP-FPM: ${PHP_INI}"
    exit 1
fi

info "Thiết lập giới hạn upload PHP là ${UPLOAD_LIMIT}..."
sed -i -E "s|^[;[:space:]]*upload_max_filesize[[:space:]]*=.*|upload_max_filesize = ${UPLOAD_LIMIT}|" "${PHP_INI}"
sed -i -E "s|^[;[:space:]]*post_max_size[[:space:]]*=.*|post_max_size = ${UPLOAD_LIMIT}|" "${PHP_INI}"
systemctl enable --now "php${PHP_VERSION}-fpm"
systemctl restart "php${PHP_VERSION}-fpm"

# Composer được yêu cầu chạy bằng root; biến này loại bỏ cảnh báo chặn của Composer.
info "Cài đặt thư viện Composer cho môi trường production..."
export COMPOSER_ALLOW_SUPERUSER=1
"php${PHP_VERSION}" /usr/bin/composer install --no-dev --optimize-autoloader --working-dir="${PROJECT_DIR}"

# Thiết lập owner và quyền truy cập an toàn cho Laravel.
info "Thiết lập owner và quyền truy cập source code..."
chown -R www-data:www-data "${PROJECT_DIR}"
find "${PROJECT_DIR}" -type d -exec chmod 755 {} +
find "${PROJECT_DIR}" -type f -exec chmod 644 {} +
chmod -R 775 "${PROJECT_DIR}/storage" "${PROJECT_DIR}/bootstrap/cache"
success "Đã hoàn tất phân quyền Laravel."

# Tạo Nginx server block, chuyển request PHP đến đúng socket của PHP-FPM.
info "Tạo cấu hình Nginx cho ${DOMAIN}..."
cat > "${NGINX_SITE}" <<EOF
server {
    listen ${NGINX_PORT};
    listen [::]:${NGINX_PORT};
server_name ${DOMAIN} ${DOMAIN_ALIASES};

    root ${PROJECT_DIR}/public;
    index index.php index.html;
    client_max_body_size ${UPLOAD_LIMIT};

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.ht {
        deny all;
    }
}
EOF

# Kích hoạt site rồi kiểm tra cấu hình trước khi restart Nginx.
ln -sfn "${NGINX_SITE}" "/etc/nginx/sites-enabled/${DOMAIN}"
nginx -t
systemctl enable --now nginx
systemctl restart nginx
success "Nginx đã được cấu hình và khởi động lại."

# Certbot tự xác thực domain, tự thêm chứng chỉ vào Nginx và redirect HTTP sang HTTPS.
if [[ "${ENABLE_SSL}" == "true" ]]; then
    CERTBOT_DOMAIN_ARGS=(-d "${DOMAIN}")
    if [[ -n "${DOMAIN_ALIASES}" ]]; then
        read -r -a DOMAIN_ALIAS_LIST <<< "${DOMAIN_ALIASES}"
        for DOMAIN_ALIAS in "${DOMAIN_ALIAS_LIST[@]}"; do
            CERTBOT_DOMAIN_ARGS+=(-d "${DOMAIN_ALIAS}")
        done
    fi

    info "Đang yêu cầu chứng chỉ Let's Encrypt và bật chuyển hướng HTTPS..."
    certbot --nginx \
        --non-interactive \
        --agree-tos \
        --email "${CERTBOT_EMAIL}" \
        --redirect \
        "${CERTBOT_DOMAIN_ARGS[@]}"
    systemctl enable --now certbot.timer
    success "SSL đã được Certbot cấu hình. Certbot sẽ tự gia hạn chứng chỉ."
fi

# Sinh script backup. Rclone remote "mydrive" phải được cấu hình trước bằng: rclone config
info "Tạo kịch bản backup tại ${BACKUP_SCRIPT}..."
cat > "${BACKUP_SCRIPT}" <<EOF
#!/usr/bin/env bash
# Tự động backup toàn diện (Database MySQL + Mã nguồn + Tệp đính kèm) lên Rclone và NAS.

set -Eeuo pipefail

PROJECT_DIR="${PROJECT_DIR}"
PROJECT_NAME="${PROJECT_NAME}"
BACKUP_DIR="/tmp"
BACKUP_FILE="\${BACKUP_DIR}/\${PROJECT_NAME}_\$(date +%Y%m%d_%H%M%S).zip"
DB_DUMP_FILE="\${PROJECT_DIR}/storage/app/auto_backup_database.sql"
RCLONE_DESTINATION="mydrive:/Server_Backups/"

cleanup() {
    rm -f "\${BACKUP_FILE}"
    rm -f "\${DB_DUMP_FILE}"
}
trap cleanup EXIT

# 1. Tự động dump database MySQL nếu có file .env
if [[ -f "\${PROJECT_DIR}/.env" ]]; then
    DB_DATABASE="\$(grep -E '^DB_DATABASE=' "\${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d '\"'\'' ' || true)"
    DB_USERNAME="\$(grep -E '^DB_USERNAME=' "\${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d '\"'\'' ' || true)"
    DB_PASSWORD="\$(grep -E '^DB_PASSWORD=' "\${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d '\"'\'' ' || true)"
    DB_HOST="\$(grep -E '^DB_HOST=' "\${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d '\"'\'' ' || true)"
    DB_PORT="\$(grep -E '^DB_PORT=' "\${PROJECT_DIR}/.env" | cut -d '=' -f2- | tr -d '\"'\'' ' || true)"

    if [[ -n "\${DB_DATABASE}" ]]; then
        echo "[INFO] Đang sao lưu cơ sở dữ liệu MySQL (\${DB_DATABASE})..."
        MYSQL_PWD="\${DB_PASSWORD:-}" mysqldump \
            -h "\${DB_HOST:-127.0.0.1}" \
            -P "\${DB_PORT:-3306}" \
            -u "\${DB_USERNAME:-root}" \
            --default-character-set=utf8mb4 \
            --routines --triggers \
            "\${DB_DATABASE}" > "\${DB_DUMP_FILE}" 2>/dev/null || true
    fi
fi

echo "[INFO] Đang đóng gói toàn bộ dự án và tệp đính kèm: \${PROJECT_DIR}"
zip -rq "\${BACKUP_FILE}" "\${PROJECT_DIR}" -x "\${PROJECT_DIR}/.git/*" "\${PROJECT_DIR}/node_modules/*"

echo "[INFO] Đang đẩy bản sao lưu lên Rclone: \${RCLONE_DESTINATION}"
rclone copy "\${BACKUP_FILE}" "\${RCLONE_DESTINATION}"

if mountpoint -q /mnt/nas; then
    echo "[INFO] NAS đã mount, đang sao chép backup vào /mnt/nas..."
    cp "\${BACKUP_FILE}" /mnt/nas/
else
    echo "[WARN] /mnt/nas chưa được mount, bỏ qua bản sao NAS."
fi

echo "[OK] Backup toàn diện hoàn tất: \${BACKUP_FILE}"
EOF
chmod 700 "${BACKUP_SCRIPT}"
success "Đã tạo script backup."

warning "Hãy cấu hình Rclone remote tên 'mydrive' bằng lệnh: rclone config"
if [[ "${ENABLE_SSL}" == "true" ]]; then
    ACCESS_URL="https://${DOMAIN}"
elif (( NGINX_PORT == 80 )); then
    ACCESS_URL="http://${DOMAIN}"
else
    ACCESS_URL="http://${DOMAIN}:${NGINX_PORT}"
fi
success "Thiết lập hoàn tất. Truy cập ${ACCESS_URL} sau khi DNS trỏ về server."
