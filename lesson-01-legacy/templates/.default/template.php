<?php
/**
 * Шаблон списка новостей - legacy версия
 *
 * АНТИПАТТЕРНЫ В ЭТОМ ФАЙЛЕ:
 * 1. Запросы к БД прямо в шаблоне (N+1)
 * 2. Бизнес-логика в шаблоне
 * 3. Использование глобальных переменных
 * 4. Inline-стили
 * 5. Отсутствие экранирования
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $USER;
?>

<div class="news-list" style="margin: 20px 0;">

    <?php if (!empty($arResult["ERROR_MESSAGE"])): ?>
        <div style="color: red; padding: 10px; border: 1px solid red;">
            <?= $arResult["ERROR_MESSAGE"] ?>
        </div>
    <?php endif; ?>

    <?php foreach ($arResult["ITEMS"] as $arItem): ?>
        <div class="news-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd;">

            <?php if ($arItem["PREVIEW_PICTURE_SRC"]): ?>
                <img src="<?= $arItem["PREVIEW_PICTURE_SRC"] ?>" alt="<?= $arItem["NAME"] ?>" style="max-width: 200px; float: left; margin-right: 15px;">
            <?php endif; ?>

            <h3 style="margin: 0 0 10px 0;">
                <a href="<?= $arItem["DETAIL_PAGE_URL"] ?>"><?= $arItem["NAME"] ?></a>
            </h3>

            <?php if ($arItem["ACTIVE_FROM_FORMATTED"]): ?>
                <div style="color: #999; font-size: 12px; margin-bottom: 10px;">
                    <?= $arItem["ACTIVE_FROM_FORMATTED"] ?>

                    <?php
                    // АНТИПАТТЕРН: Еще один запрос в шаблоне! Дублирует логику из component.php
                    if ($arItem["CREATED_BY"] && empty($arItem["AUTHOR_NAME"])) {
                        $rsAuthor = CUser::GetByID($arItem["CREATED_BY"]);
                        if ($arAuthor = $rsAuthor->Fetch()) {
                            echo " | Автор: " . $arAuthor["NAME"];
                        }
                    } else {
                        echo " | Автор: " . $arItem["AUTHOR_NAME"];
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($arItem["PREVIEW_TEXT"]): ?>
                <div class="news-preview" style="margin-bottom: 10px;">
                    <?= $arItem["PREVIEW_TEXT"] ?>
                </div>
            <?php endif; ?>

            <a href="<?= $arItem["DETAIL_PAGE_URL"] ?>" style="color: blue;">Подробнее →</a>

            <?php
            // АНТИПАТТЕРН: Бизнес-логика в шаблоне
            if ($USER->IsAdmin() || $arItem["CAN_EDIT"]):
            ?>
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ccc;">
                    <a href="<?= $arItem["EDIT_LINK"] ?>" style="color: orange;">
                        [Редактировать]
                    </a>

                    <?php
                    // АНТИПАТТЕРН: Запрос в шаблоне для получения доп. информации
                    $rsSection = CIBlockSection::GetByID($arItem["IBLOCK_SECTION_ID"]);
                    if ($arSection = $rsSection->Fetch()) {
                        echo " | Раздел: " . $arSection["NAME"];
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div style="clear: both;"></div>
        </div>
    <?php endforeach; ?>

    <?php if ($arResult["TOTAL_COUNT"] > 0): ?>
        <div style="color: #666; font-size: 12px;">
            Всего новостей: <?= $arResult["TOTAL_COUNT"] ?>
        </div>
    <?php endif; ?>

</div>
