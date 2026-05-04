<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * CustomersExport handles exporting customer data to Excel
 * Requirements: 7.2
 */
class CustomersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $customers;

    public function __construct(Collection $customers)
    {
        $this->customers = $customers;
    }

    /**
     * Return the collection to export
     */
    public function collection()
    {
        return $this->customers;
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Partner Name (*)',
            'Tax code (*)',
            'Abv Name (*)',
            'First Name (*)',
            'Last Name',
            'Mr/Ms/Mrs',
            'PIC Job Title (*)',
            'PIC Phone (*)',
            'PIC Email (*)',
            'AM',
        ];
    }

    /**
     * Map each row to the desired format
     */
    public function map($customer_or_contact): array
    {
        // This will be called for each item in the collection.
        // We will adjust the collection in the controller or here if possible.
        // Actually, Maatwebsite Excel calls map for each item in the collection.
        // If we want multiple rows per customer (one per contact), we should 
        // change how the collection is provided.
        
        // For now, let's assume the collection passed in is already flat-mapped if it's contacts,
        // or we handle it if it's a Customer.
        
        if ($customer_or_contact instanceof \App\Models\Contact) {
            $contact = $customer_or_contact;
            $customer = $contact->customer;
            return [
                $customer->name ?? '',
                $customer->tax_code ?? '',
                $customer->abv_name ?? '',
                $contact->first_name ?? $contact->name,
                $contact->last_name ?? '',
                $contact->title ?? '',
                $contact->position ?? '',
                $contact->phone ?? '',
                $contact->email ?? '',
                $customer->am ?? '',
            ];
        }

        // Fallback for Customer without contacts (if any)
        return [
            $customer_or_contact->name ?? '',
            $customer_or_contact->tax_code ?? '',
            $customer_or_contact->abv_name ?? '',
            '', '', '', '', '', '',
            $customer_or_contact->am ?? '',
        ];
    }
}

