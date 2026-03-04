<?php
/**
 * Урок 2: Шаблон - пока без изменений
 *
 * На этом этапе шаблон остаётся прежним.
 * Очистка шаблона будет в следующих уроках.
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<div class="news-list">

    <?php if (empty($arResult["ITEMS"])): ?>
        <div class="news-list__empty">
            Новости не найдены
        </div>
    <?php endif; ?>

    <?php foreach ($arResult["ITEMS"] as $arItem): ?>
        <div class="news-item">

            <?php if (!empty($arItem["PREVIEW_PICTURE_SRC"])): ?>
                <img
                    src="<?= htmlspecialchars($arItem["PREVIEW_PICTURE_SRC"]) ?>"
                    alt="<?= htmlspecialchars($arItem["NAME"]) ?>"
                    class="news-item__image"
                >
            <?php endif; ?>

            <h3 class="news-item__title">
                <a href="<?= htmlspecialchars($arItem["DETAIL_PAGE_URL"]) ?>">
                    <?= htmlspecialchars($arItem["NAME"]) ?>
                </a>
            </h3>

            <?php if (!empty($arItem["ACTIVE_FROM_FORMATTED"])): ?>
                <div class="news-item__date">
                    <?= $arItem["ACTIVE_FROM_FORMATTED"] ?>

                    <?php if (!empty($arItem["AUTHOR_NAME"])): ?>
                        | Автор: <?= htmlspecialchars($arItem["AUTHOR_NAME"]) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($arItem["PREVIEW_TEXT"])): ?>
                <div class="news-item__preview">
                    <?= $arItem["PREVIEW_TEXT"] ?>
                </div>
            <?php endif; ?>

            <a href="<?= htmlspecialchars($arItem["DETAIL_PAGE_URL"]) ?>" class="news-item__more">
                Подробнее →
            </a>

        </div>
    <?php endforeach; ?>

    <?php if ($arResult["TOTAL_COUNT"] > 0): ?>
        <div class="news-list__total">
            Всего новостей: <?= $arResult["TOTAL_COUNT"] ?>
        </div>
    <?php endif; ?>

</div>
