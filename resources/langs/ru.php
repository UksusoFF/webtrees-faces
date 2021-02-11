<?php

// Note: Commented keys already exist in webtrees translations.

use Fisharebest\Webtrees\I18N;

return [
    // Frontend: Modals
    'Enter individuals id or something else' => 'Введите id персоны или что-нибудь еще',
    'This operation can not be undone' => 'Эту операцию нельзя отменить',
    'You have enabled "Create links" feature' => 'У вас включена опция "Создавать связи"',
    'Linked individuals will not be detached from media' => 'Связанные персоны не будут отвязаны от медиа файла',
    'Are you sure that want delete %s from image?' => 'Вы действительно хотите удалить %s с изображения?',
    // Config: JavaScript
    'Are you sure?' => 'Вы уверены?',
    'Read more' => 'Подробнее',
    // Config
    'Reset filters' => 'Сбросить фильтры',
    // Config: Missed
    'Missed' => 'Потерянные',
    // 'Remove' => 'Удалить',
    'Remove all records without media' => 'Удалить все записи без медиа',
    'Find' => 'Найти',
    'Try to find missed records by filename' => 'Попытаться найти потерянные записи по имени',
    // Config: Settings
    'Settings' => 'Настройки',
    'Read XMP data' => 'Читать XMP данные',
    'Read and show XMP data (such as Goggle Picasa face tags) from media file' => 'Читать и показывать XMP данные (например отметки лиц в Goggle Picasa) из медиа файла',
    'Create links' => 'Создавать связи',
    'Link individual with media when mark them on photo' => 'Связывать персону с медиа при добавлении ее на фото',
    'Show meta' => 'Показывать мету',
    'Load and show information from linked fact' => 'Загружать и показывать информацию из связанного факта',
    'Show tab' => 'Показывать вкладку',
    'Show tab on individuals page' => 'Показывать вкладку на странице персоны',
    // Config: Table
    // 'Media' => 'Медиа',
    // 'Notes' => 'Примечания',
    // 'Status' => 'Статус',
    'Actions' => 'Действия',
    // Config: Messages
    '%s record' . I18N::PLURAL . '%s records' => '%s запись' . I18N::PLURAL . '%s записи' . I18N::PLURAL . '%s записей',
    'has been deleted' . I18N::PLURAL . 'have been deleted' => 'была удалена' . I18N::PLURAL . 'было удалено' . I18N::PLURAL . 'было удалено',
    'has been repaired' . I18N::PLURAL . 'have been repaired' => 'была восстановлена' . I18N::PLURAL . 'было восстановлено'. 'было восстановлено',
    // 'Enabled' => 'Включено',
    'Disabled' => 'Выключено',
];
