<?php

namespace App\Services;

class NotificationEventRegistry
{
    public function all(): array
    {
        return [
            'tdr_process.overdue_start' => [
                'label' => 'TDR process overdue start',
                'description' => 'A process passed its standard days and is still not finished.',
                'default_severity' => 'danger',
                'default_title' => 'Process overdue',
                'default_message' => 'WO {workorder_no}: {process_name} for {part_number} overdue by {overdue_days} days.',
                'dynamic_recipients' => [
                    'tdr_process_user' => 'TDR process assigned user',
                    'process_notify_user' => 'Process responsible user',
                    'system_admins' => 'System admins',
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
            'workorder.assigned' => [
                'label' => 'Workorder assigned',
                'description' => 'A workorder was assigned to a user.',
                'default_severity' => 'info',
                'default_title' => 'Workorder assigned',
                'default_message' => 'Workorder {workorder_no} was assigned to you by {actor_name}.',
                'dynamic_recipients' => [
                    'assigned_user' => 'Assigned user',
                ],
                'variables' => [
                    'workorder_no',
                    'workorder_id',
                    'actor_name',
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
                    'system_admins' => 'System admins',
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
                    'system_admins' => 'System admins',
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
            'user.birthday_2days' => [
                'label' => 'Birthday in 2 days',
                'description' => 'A user has a birthday in 2 days.',
                'default_severity' => 'info',
                'default_title' => 'Birthday in 2 days',
                'default_message' => '{birthday_user_name} has a birthday in 2 days.',
                'dynamic_recipients' => [
                    'system_admins' => 'System admins',
                ],
                'variables' => [
                    'birthday_user_name',
                    'birthday_age',
                ],
            ],
            'user.birthday_today' => [
                'label' => 'Birthday today',
                'description' => 'Today is a user birthday.',
                'default_severity' => 'success',
                'default_title' => 'Birthday today',
                'default_message' => 'Today is {birthday_user_name} birthday.',
                'dynamic_recipients' => [
                    'all_users' => 'All users',
                    'birthday_user' => 'Birthday user',
                ],
                'variables' => [
                    'birthday_user_name',
                    'birthday_age',
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
