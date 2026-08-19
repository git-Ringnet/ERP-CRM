<?php
echo "<h2>Kiểm tra cấu hình PHP</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Cấu hình</th><th>Giá trị hiện tại</th><th>Yêu cầu</th></tr>";

$configs = [
    'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'required' => '200M'],
    'post_max_size' => ['current' => ini_get('post_max_size'), 'required' => '200M'],
    'max_execution_time' => ['current' => ini_get('max_execution_time'), 'required' => '600'],
    'max_input_time' => ['current' => ini_get('max_input_time'), 'required' => '600'],
    'memory_limit' => ['current' => ini_get('memory_limit'), 'required' => '1024M'],
];

foreach ($configs as $name => $values) {
    $color = ($values['current'] == $values['required']) ? 'green' : 'red';
    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td style='color: $color;'><strong>{$values['current']}</strong></td>";
    echo "<td>{$values['required']}</td>";
    echo "</tr>";
}

echo "</table>";
?>
