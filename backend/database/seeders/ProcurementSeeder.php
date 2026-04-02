<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\ApprovalStep;
use App\Models\Disbursement;
use Illuminate\Database\Seeder;

class ProcurementSeeder extends Seeder
{
    public function run(): void
    {
        // --- Vendors ---
        $vendors = [
            [
                'name' => 'Dangote Office Supplies',
                'contact_person' => 'Ibrahim Musa',
                'email' => 'sales@dangoteoffice.com',
                'phone' => '+234 801 234 5678',
                'address' => '45 Industrial Avenue, Ikeja',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'tax_id' => 'TIN-00123456',
                'bank_name' => 'First Bank of Nigeria',
                'bank_account_number' => '2012345678',
                'notes' => 'Reliable supplier for office stationery and furniture',
                'status' => 'active',
            ],
            [
                'name' => 'MedServe Nigeria Ltd',
                'contact_person' => 'Dr. Amina Bello',
                'email' => 'procurement@medserve.ng',
                'phone' => '+234 802 987 6543',
                'address' => '12 Pharma Road, Wuse',
                'city' => 'Abuja',
                'state' => 'FCT',
                'country' => 'Nigeria',
                'tax_id' => 'TIN-00234567',
                'bank_name' => 'Zenith Bank',
                'bank_account_number' => '1098765432',
                'notes' => 'Medical and health supplies vendor',
                'status' => 'active',
            ],
            [
                'name' => 'TechZone Solutions',
                'contact_person' => 'Chidi Eze',
                'email' => 'info@techzone.ng',
                'phone' => '+234 803 456 7890',
                'address' => '8 Silicon Close, Lekki Phase 1',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'tax_id' => 'TIN-00345678',
                'bank_name' => 'GTBank',
                'bank_account_number' => '0176543210',
                'notes' => 'IT equipment, laptops, printers, and networking gear',
                'status' => 'active',
            ],
            [
                'name' => 'CleanPro Services',
                'contact_person' => 'Funke Adeyemi',
                'email' => 'orders@cleanpro.ng',
                'phone' => '+234 805 678 1234',
                'address' => '22 Sanitation Lane, Surulere',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'tax_id' => 'TIN-00456789',
                'bank_name' => 'Access Bank',
                'bank_account_number' => '0023456789',
                'notes' => 'Cleaning supplies and janitorial equipment',
                'status' => 'active',
            ],
            [
                'name' => 'Sahel Agro & Food Supply',
                'contact_person' => 'Yusuf Bala',
                'email' => 'supply@sahelagro.com',
                'phone' => '+234 806 321 9876',
                'address' => '5 Market Road, Kano',
                'city' => 'Kano',
                'state' => 'Kano',
                'country' => 'Nigeria',
                'tax_id' => 'TIN-00567890',
                'bank_name' => 'UBA',
                'bank_account_number' => '2087654321',
                'notes' => 'Food supplies and agricultural products for relief programs',
                'status' => 'active',
            ],
            [
                'name' => 'PrintWorks Nigeria',
                'contact_person' => 'Ngozi Okoro',
                'email' => 'hello@printworks.ng',
                'phone' => '+234 807 111 2233',
                'address' => '14 Press Street, Maryland',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'tax_id' => 'TIN-00678901',
                'bank_name' => 'Stanbic IBTC',
                'bank_account_number' => '0034567891',
                'notes' => 'Printing services — brochures, banners, training materials',
                'status' => 'inactive',
            ],
        ];

        foreach ($vendors as $v) {
            Vendor::updateOrCreate(['name' => $v['name']], $v);
        }
        $this->command->info('Seeded ' . count($vendors) . ' vendors');

        // --- Inventory Items ---
        $inventoryItems = [
            ['name' => 'A4 Printing Paper (Ream)', 'sku' => 'OFF-A4P-001', 'category' => 'Office Supplies', 'unit' => 'ream', 'quantity_in_stock' => 150, 'reorder_level' => 50, 'unit_cost' => 3500, 'location' => 'Store Room A', 'status' => 'active'],
            ['name' => 'Ballpoint Pen (Box of 50)', 'sku' => 'OFF-PEN-001', 'category' => 'Office Supplies', 'unit' => 'box', 'quantity_in_stock' => 30, 'reorder_level' => 10, 'unit_cost' => 2500, 'location' => 'Store Room A', 'status' => 'active'],
            ['name' => 'HP LaserJet Toner Cartridge', 'sku' => 'OFF-TNR-001', 'category' => 'Office Supplies', 'unit' => 'pcs', 'quantity_in_stock' => 8, 'reorder_level' => 5, 'unit_cost' => 45000, 'location' => 'Store Room A', 'status' => 'active'],
            ['name' => 'Hand Sanitizer (5L)', 'sku' => 'MED-SAN-001', 'category' => 'Medical Supplies', 'unit' => 'pcs', 'quantity_in_stock' => 20, 'reorder_level' => 10, 'unit_cost' => 8500, 'location' => 'Medical Store', 'status' => 'active'],
            ['name' => 'First Aid Kit (Standard)', 'sku' => 'MED-FAK-001', 'category' => 'Medical Supplies', 'unit' => 'pcs', 'quantity_in_stock' => 12, 'reorder_level' => 5, 'unit_cost' => 25000, 'location' => 'Medical Store', 'status' => 'active'],
            ['name' => 'Laptop — HP ProBook 450 G10', 'sku' => 'IT-LAP-001', 'category' => 'IT Equipment', 'unit' => 'pcs', 'quantity_in_stock' => 3, 'reorder_level' => 2, 'unit_cost' => 750000, 'location' => 'IT Store', 'status' => 'active'],
            ['name' => 'USB Flash Drive 64GB', 'sku' => 'IT-USB-001', 'category' => 'IT Equipment', 'unit' => 'pcs', 'quantity_in_stock' => 25, 'reorder_level' => 10, 'unit_cost' => 5000, 'location' => 'IT Store', 'status' => 'active'],
            ['name' => 'Multipurpose Floor Cleaner (5L)', 'sku' => 'CLN-FLC-001', 'category' => 'Cleaning Supplies', 'unit' => 'pcs', 'quantity_in_stock' => 6, 'reorder_level' => 5, 'unit_cost' => 4500, 'location' => 'Janitorial Closet', 'status' => 'active'],
            ['name' => 'Disposable Gloves (Box of 100)', 'sku' => 'MED-GLV-001', 'category' => 'Medical Supplies', 'unit' => 'box', 'quantity_in_stock' => 5, 'reorder_level' => 10, 'unit_cost' => 6500, 'location' => 'Medical Store', 'status' => 'active'],
            ['name' => 'Projector — Epson EB-X51', 'sku' => 'IT-PRJ-001', 'category' => 'IT Equipment', 'unit' => 'pcs', 'quantity_in_stock' => 2, 'reorder_level' => 1, 'unit_cost' => 350000, 'location' => 'IT Store', 'status' => 'active'],
            ['name' => 'Rice (50kg Bag)', 'sku' => 'FD-RIC-001', 'category' => 'Food Supplies', 'unit' => 'bag', 'quantity_in_stock' => 0, 'reorder_level' => 20, 'unit_cost' => 72000, 'location' => 'Warehouse', 'status' => 'out_of_stock'],
            ['name' => 'Cooking Oil (25L)', 'sku' => 'FD-OIL-001', 'category' => 'Food Supplies', 'unit' => 'pcs', 'quantity_in_stock' => 0, 'reorder_level' => 10, 'unit_cost' => 45000, 'location' => 'Warehouse', 'status' => 'out_of_stock'],
        ];

        foreach ($inventoryItems as $item) {
            InventoryItem::updateOrCreate(['sku' => $item['sku']], $item);
        }
        $this->command->info('Seeded ' . count($inventoryItems) . ' inventory items');

        // --- Purchase Orders & Items ---
        $department = Department::where('name', 'Administration')->first();
        $employee = Employee::where('status', 'active')->first();
        $admin = User::where('role', 'admin')->first();
        $vendorOffice = Vendor::where('name', 'Dangote Office Supplies')->first();
        $vendorTech = Vendor::where('name', 'TechZone Solutions')->first();
        $vendorMed = Vendor::where('name', 'MedServe Nigeria Ltd')->first();

        if ($department && $employee && $vendorOffice) {
            // PO 1 — Office Supplies (Received)
            $po1 = PurchaseOrder::updateOrCreate(
                ['po_number' => 'PO-202603-0001'],
                [
                    'vendor_id' => $vendorOffice->id,
                    'department_id' => $department->id,
                    'requested_by' => $employee->id,
                    'submitted_by' => $admin?->id,
                    'order_date' => '2026-03-01',
                    'expected_delivery_date' => '2026-03-10',
                    'actual_delivery_date' => '2026-03-08',
                    'subtotal' => 127500,
                    'tax_amount' => 9562.50,
                    'discount_amount' => 0,
                    'total_amount' => 137062.50,
                    'currency' => 'NGN',
                    'notes' => 'Monthly office supplies restocking',
                    'status' => 'received',
                    'payment_status' => 'paid',
                ]
            );

            $paperItem = InventoryItem::where('sku', 'OFF-A4P-001')->first();
            $penItem = InventoryItem::where('sku', 'OFF-PEN-001')->first();
            $tonerItem = InventoryItem::where('sku', 'OFF-TNR-001')->first();

            if ($paperItem) {
                PurchaseOrderItem::updateOrCreate(
                    ['purchase_order_id' => $po1->id, 'description' => 'A4 Printing Paper (Ream)'],
                    ['inventory_item_id' => $paperItem->id, 'quantity' => 20, 'received_quantity' => 20, 'unit' => 'ream', 'unit_price' => 3500, 'total_price' => 70000]
                );
            }
            if ($penItem) {
                PurchaseOrderItem::updateOrCreate(
                    ['purchase_order_id' => $po1->id, 'description' => 'Ballpoint Pen (Box of 50)'],
                    ['inventory_item_id' => $penItem->id, 'quantity' => 5, 'received_quantity' => 5, 'unit' => 'box', 'unit_price' => 2500, 'total_price' => 12500]
                );
            }
            if ($tonerItem) {
                PurchaseOrderItem::updateOrCreate(
                    ['purchase_order_id' => $po1->id, 'description' => 'HP LaserJet Toner Cartridge'],
                    ['inventory_item_id' => $tonerItem->id, 'quantity' => 1, 'received_quantity' => 1, 'unit' => 'pcs', 'unit_price' => 45000, 'total_price' => 45000]
                );
            }
        }

        if ($department && $employee && $vendorTech) {
            // PO 2 — IT Equipment (Pending approval)
            $po2 = PurchaseOrder::updateOrCreate(
                ['po_number' => 'PO-202603-0002'],
                [
                    'vendor_id' => $vendorTech->id,
                    'department_id' => $department->id,
                    'requested_by' => $employee->id,
                    'submitted_by' => $admin?->id,
                    'order_date' => '2026-03-15',
                    'expected_delivery_date' => '2026-03-25',
                    'subtotal' => 2250000,
                    'tax_amount' => 168750,
                    'discount_amount' => 50000,
                    'total_amount' => 2368750,
                    'currency' => 'NGN',
                    'notes' => 'Laptops for new program staff',
                    'status' => 'submitted',
                    'payment_status' => 'unpaid',
                ]
            );

            $laptopItem = InventoryItem::where('sku', 'IT-LAP-001')->first();
            if ($laptopItem) {
                PurchaseOrderItem::updateOrCreate(
                    ['purchase_order_id' => $po2->id, 'description' => 'Laptop — HP ProBook 450 G10'],
                    ['inventory_item_id' => $laptopItem->id, 'quantity' => 3, 'received_quantity' => 0, 'unit' => 'pcs', 'unit_price' => 750000, 'total_price' => 2250000]
                );
            }
        }

        if ($department && $employee && $vendorMed) {
            // PO 3 — Medical Supplies (Draft)
            PurchaseOrder::updateOrCreate(
                ['po_number' => 'PO-202603-0003'],
                [
                    'vendor_id' => $vendorMed->id,
                    'department_id' => $department->id,
                    'requested_by' => $employee->id,
                    'order_date' => '2026-03-20',
                    'expected_delivery_date' => '2026-04-01',
                    'subtotal' => 202500,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 202500,
                    'currency' => 'NGN',
                    'notes' => 'First aid and hygiene supplies for field teams',
                    'status' => 'draft',
                    'payment_status' => 'unpaid',
                ]
            );
        }

        $this->command->info('Seeded 3 purchase orders with items');

        // --- Requisitions & Items ---
        if ($department && $employee) {
            $req1 = Requisition::updateOrCreate(
                ['requisition_number' => 'REQ-202603-0001'],
                [
                    'department_id' => $department->id,
                    'requested_by' => $employee->id,
                    'submitted_by' => $admin?->id,
                    'title' => 'Monthly Office Supplies Restock',
                    'justification' => 'Running low on printing paper and toner cartridges for the Abuja office.',
                    'priority' => 'medium',
                    'needed_by' => '2026-03-10',
                    'estimated_cost' => 127500,
                    'notes' => 'Linked to PO-202603-0001',
                    'status' => 'fulfilled',
                ]
            );

            $paperItem = InventoryItem::where('sku', 'OFF-A4P-001')->first();
            $tonerItem = InventoryItem::where('sku', 'OFF-TNR-001')->first();
            if ($paperItem) {
                RequisitionItem::updateOrCreate(
                    ['requisition_id' => $req1->id, 'description' => 'A4 Printing Paper'],
                    ['inventory_item_id' => $paperItem->id, 'quantity' => 20, 'unit' => 'ream', 'estimated_unit_cost' => 3500, 'estimated_total_cost' => 70000]
                );
            }
            if ($tonerItem) {
                RequisitionItem::updateOrCreate(
                    ['requisition_id' => $req1->id, 'description' => 'HP LaserJet Toner Cartridge'],
                    ['inventory_item_id' => $tonerItem->id, 'quantity' => 1, 'unit' => 'pcs', 'estimated_unit_cost' => 45000, 'estimated_total_cost' => 45000]
                );
            }

            $req2 = Requisition::updateOrCreate(
                ['requisition_number' => 'REQ-202603-0002'],
                [
                    'department_id' => $department->id,
                    'requested_by' => $employee->id,
                    'submitted_by' => $admin?->id,
                    'title' => 'IT Equipment for New Staff',
                    'justification' => '3 new program officers joining in April need laptops and accessories.',
                    'priority' => 'high',
                    'needed_by' => '2026-03-25',
                    'estimated_cost' => 2250000,
                    'status' => 'submitted',
                ]
            );

            $laptopItem = InventoryItem::where('sku', 'IT-LAP-001')->first();
            if ($laptopItem) {
                RequisitionItem::updateOrCreate(
                    ['requisition_id' => $req2->id, 'description' => 'Laptop — HP ProBook 450 G10'],
                    ['inventory_item_id' => $laptopItem->id, 'quantity' => 3, 'unit' => 'pcs', 'estimated_unit_cost' => 750000, 'estimated_total_cost' => 2250000]
                );
            }

            Requisition::updateOrCreate(
                ['requisition_number' => 'REQ-202603-0003'],
                [
                    'department_id' => $department->id,
                    'requested_by' => $employee->id,
                    'title' => 'Emergency Medical Supply Request',
                    'justification' => 'Field teams report depleted first aid kits and hand sanitizer stock.',
                    'priority' => 'urgent',
                    'needed_by' => '2026-03-22',
                    'estimated_cost' => 202500,
                    'status' => 'approved',
                    'submitted_by' => $admin?->id,
                ]
            );

            $this->command->info('Seeded 3 requisitions with items');
        }

        // --- Approval Steps for submitted/approved items ---
        $po2 = PurchaseOrder::where('po_number', 'PO-202603-0002')->first();
        if ($po2 && $admin) {
            ApprovalStep::updateOrCreate(
                ['approvable_type' => 'purchase_order', 'approvable_id' => $po2->id, 'step_order' => 1],
                ['step_type' => 'manager_review', 'step_label' => 'Manager Review', 'status' => 'pending']
            );
            ApprovalStep::updateOrCreate(
                ['approvable_type' => 'purchase_order', 'approvable_id' => $po2->id, 'step_order' => 2],
                ['step_type' => 'finance_check', 'step_label' => 'Finance Verification', 'status' => 'pending']
            );
            ApprovalStep::updateOrCreate(
                ['approvable_type' => 'purchase_order', 'approvable_id' => $po2->id, 'step_order' => 3],
                ['step_type' => 'operations_approval', 'step_label' => 'Operations Approval', 'status' => 'pending']
            );
            ApprovalStep::updateOrCreate(
                ['approvable_type' => 'purchase_order', 'approvable_id' => $po2->id, 'step_order' => 4],
                ['step_type' => 'executive_approval', 'step_label' => 'Executive Director Approval', 'status' => 'pending']
            );
            $this->command->info('Seeded approval steps for PO-202603-0002');
        }

        $req3 = Requisition::where('requisition_number', 'REQ-202603-0003')->first();
        if ($req3 && $admin) {
            ApprovalStep::updateOrCreate(
                ['approvable_type' => 'requisition', 'approvable_id' => $req3->id, 'step_order' => 1],
                ['step_type' => 'manager_review', 'step_label' => 'Manager Review', 'status' => 'approved', 'acted_by' => $admin->id, 'acted_at' => now()->subDays(2)]
            );
            ApprovalStep::updateOrCreate(
                ['approvable_type' => 'requisition', 'approvable_id' => $req3->id, 'step_order' => 2],
                ['step_type' => 'finance_check', 'step_label' => 'Finance Verification', 'status' => 'approved', 'acted_by' => $admin->id, 'acted_at' => now()->subDay()]
            );
            $this->command->info('Seeded approval steps for REQ-202603-0003');
        }

        // --- Sample Disbursement for received PO ---
        $po1 = PurchaseOrder::where('po_number', 'PO-202603-0001')->first();
        if ($po1 && $admin) {
            Disbursement::updateOrCreate(
                ['purchase_order_id' => $po1->id, 'payment_reference' => 'PAY-202603-001'],
                [
                    'disbursed_by' => $admin->id,
                    'amount' => 137062.50,
                    'payment_method' => 'bank_transfer',
                    'payment_date' => '2026-03-09',
                    'notes' => 'Full payment for office supplies PO',
                ]
            );
            $this->command->info('Seeded 1 disbursement for PO-202603-0001');
        }
    }
}
