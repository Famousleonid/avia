<?php

/**
 * Доступ к «фичам» (пункты меню, закрытые маршруты): роль ИЛИ явный user_id.
 * Gate-имя: feature.{ключ}, например feature.reports_beta
 *
 * - roles — список имён ролей; достаточно одной совпавшей.
 * - user_ids — дополнительный whitelist по id пользователей (int).
 * - allow_is_admin — если true, пользователь с is_admin проходит (User::isAdmin()).
 * Пустые roles и user_ids и allow_is_admin false → доступа нет.
 */
return [

    'paint' => [
        'roles' => ['Admin', 'Manager'],
        'user_ids' => [2, 5, 8],
        'allow_is_admin' => true,
    ],

    'reports_beta' => [
        'roles' => ['Admin', 'Manager'],
        'user_ids' => [12, 47, 103],
    ],

    // пример: только перечисленные пользователи (роли не используются)
    'one_off_tool' => [
        'roles' => [],
        'user_ids' => [1, 2],
    ],

];
