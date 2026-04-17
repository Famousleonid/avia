<?php

namespace App\Services;

class NotificationEventRegistry
{
    public function all(): array
    {
        return [
            'tdr_process.overdue_start' => [
                'label' => 'TDR process overdue start',
                'description' => 'A TDR process is not finished after its standard days.',
                'default_severity' => 'danger',
                'default_title' => 'Process overdue',
                'default_message' => 'WO {workorder_no}: {process_name} for {part_number} overdue by {overdue_days} days.',
                'dynamic_recipients' => [
                    'tdr_process_user' => 'TDR process assigned user',
                    'process_notify_user' => 'Process responsible user',
                    'workorder_technician' => 'Workorder technician',
                ],
                'variables' => [
                    'workorder_no',
                    'workorder_id',
                    'owner_name',
                    'process_name',
                    'part_number',
                    'start_date',
                    'std_days',
                    'overdue_days',
                ],
            ],
            'workorder.approved' => [
                'label' => 'Workorder approved',
                'description' => 'A workorder was approved.',
                'default_severity' => 'success',
                'default_title' => 'Approved',
                'default_message' => 'Workorder {workorder_no} approved by {actor_name}.',
                'dynamic_recipients' => [
                    'workorder_technician' => 'Workorder technician',
                ],
                'variables' => [
                    'workorder_no',
                    'workorder_id',
                    'actor_name',
                ],
            ],
            'workorder.draft_created' => [
                'label' => 'Draft workorder created',
                'description' => 'A Shipping user created a draft workorder from the mobile page.',
                'default_severity' => 'info',
                'default_title' => 'Draft Workorder created',
                'default_message' => 'Draft WO {workorder_no} created by {actor_name}. Unit: {part_number}.',
                'dynamic_recipients' => [
                    'draft_creator' => 'Draft creator',
                ],
                'variables' => [
                    'workorder_no',
                    'workorder_id',
                    'actor_name',
                    'part_number',
                    'serial_number',
                    'customer_name',
                ],
            ],
        ];
    }

    public function get(string $eventKey): ?array
    {
        return $this->all()[$eventKey] ?? null;
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }
}
