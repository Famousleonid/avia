<?php

/**
 * Подсказки для AI-ассистента по навигации в админке.
 * Редактируйте при изменении меню (resources/views/components/admin_menu_sidebar.blade.php).
 * Ключи: en / ru — тексты; совпадают с подписями в сайдбаре где возможно.
 */
return [

    'sidebar' => [
        'en' => <<<'TXT'
LEFT SIDEBAR (main navigation):
- "Workorder" — list of workorders; open a row to go to the main workorder page (tabs, tasks, photos area).
- "Training" — training list.
- "Training All" — visible for Admin / Team Leader / Manager only.
- "Techniks" — users (technicians).
- "Materials" — materials.
- "Library" (expandable, Admin only): General Tasks, Tasks, Roles, Teams, Builders, Vendors, Codes, Process Names, Scopes, Planes, Instructions, Necessaries.
- "Customers", "Manuals", "Component CMM", "Replaceable Parts", "Processes" — Admin only.
- "Log" (activity log), "Mobile" — Admin / is_admin() only.
- Theme toggle at the bottom (dark/light).
TXT
        ,
        'ru' => <<<'TXT'
ЛЕВОЕ МЕНЮ:
- «Workorder» — список воркордеров; клик по строке открывает основную страницу воркордера (вкладки, задачи, фото).
- «Training» — тренинги.
- «Training All» — для Admin / Team Leader / Manager.
- «Techniks» — пользователи (техники).
- «Materials» — материалы.
- «Library» (раскрывается, только Admin): General Tasks, Tasks, Roles, Teams, Builders, Vendors, Codes, Process Names, Scopes, Planes, Instructions, Necessaries.
- «Customers», «Manuals», «Component CMM», «Replaceable Parts», «Processes» — только Admin.
- «Log», «Mobile» — только для админов (как в меню).
- Внизу — переключение темы.
TXT
        ,
    ],

    'workorder_main_page' => [
        'en' => <<<'TXT'
On the MAIN WORKORDER page (after opening a workorder from the list):
- Top bar: workorder number (w …), approval badge, info icon, "TDR Report" (hammer), "Pictures" (images icon; badge may show photo count), "Logs" / second TDR (role-dependent).
- To view or manage photos: click the "Pictures" button in the top bar (opens the photos modal/UI), not a separate sidebar item.
- Training shortcuts may appear on the right side of the top area when configured.
TXT
        ,
        'ru' => <<<'TXT'
На ОСНОВНОЙ СТРАНИЦЕ ВОРКОРДЕРА (после открытия из списка):
- Верхняя панель: номер воркордера (w …), бейдж согласования, иконка информации, кнопка «TDR Report», кнопка «Pictures» (иконка изображений; может быть бейдж с числом фото), «Logs» / второй TDR (зависит от роли).
- Чтобы смотреть/работать с фото: нажать кнопку «Pictures» в верхней панели (откроется модальное окно/интерфейс фото), отдельного пункта «фото» в боковом меню нет.
TXT
        ,
    ],

    'rules' => [
        'en' => <<<'TXT'
UI HELP RULES:
- Give numbered steps: menu name → submenu → button/tab name exactly as in this guide.
- If the user is not Admin, do not tell them to open "Library" or other Admin-only items; say that this screen requires administrator rights.
- If "current route" is provided in context, you may start with "You are already on …" and then what to click next.
- If the user asks for a screen not described here, say honestly that you only have this map and suggest starting from "Workorder" in the sidebar or ask an admin.
- Never invent button names or menu items that are not listed here.
TXT
        ,
        'ru' => <<<'TXT'
ПРАВИЛА ПОДСКАЗОК:
- Нумерованные шаги: пункт меню → подменю → кнопка/вкладка — формулировки как в этом справочнике.
- Если пользователь не Admin — не отправляйте в «Library» и другие пункты только для админов; скажите, что нужны права администратора.
- Если в контексте передан текущий маршрут — можно начать с «Вы уже на странице …» и что нажать дальше.
- Если нужного экрана нет в справочнике — честно скажите, что карта неполная, и предложите начать с «Workorder» в меню или уточнить у админа.
- Не выдумывайте названия кнопок и пунктов меню.
TXT
        ,
    ],
];
