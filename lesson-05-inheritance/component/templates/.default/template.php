<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
?>

<div class="news-list">

    <?php if (empty($arResult['ITEMS'])): ?>
        <div class="news-list__empty">Новости не найдены</div>
    <?php else: ?>

        <?php foreach ($arResult['ITEMS'] as $arItem): ?>
            <article class="news-item">

                <?php if (!empty($arItem['PREVIEW_PICTURE_SRC'])): ?>
                    <img
                        src="<?= htmlspecialchars($arItem['PREVIEW_PICTURE_SRC']) ?>"
                        alt="<?= htmlspecialchars($arItem['NAME']) ?>"
                        class="news-item__image"
                        loading="lazy"
                    >
                <?php endif; ?>

                <h3 class="news-item__title">
                    <a href="<?= htmlspecialchars($arItem['DETAIL_PAGE_URL']) ?>">
                        <?= htmlspecialchars($arItem['NAME']) ?>
                    </a>
                </h3>

                <?php if (!empty($arItem['ACTIVE_FROM_FORMATTED']) || !empty($arItem['AUTHOR_NAME'])): ?>
                    <div class="news-item__meta">
                        <?php if (!empty($arItem['ACTIVE_FROM_FORMATTED'])): ?>
                            <time class="news-item__date"><?= $arItem['ACTIVE_FROM_FORMATTED'] ?></time>
                        <?php endif; ?>
                        <?php if (!empty($arItem['AUTHOR_NAME'])): ?>
                            <span class="news-item__author"><?= htmlspecialchars($arItem['AUTHOR_NAME']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($arItem['PREVIEW_TEXT'])): ?>
                    <div class="news-item__preview"><?= $arItem['PREVIEW_TEXT'] ?></div>
                <?php endif; ?>

                <a href="<?= htmlspecialchars($arItem['DETAIL_PAGE_URL']) ?>" class="news-item__more">Подробнее</a>

                <?php if ($arResult['IS_ADMIN']): ?>
                    <div class="news-item__admin">
                        <a href="/bitrix/admin/iblock_element_edit.php?ID=<?= (int)$arItem['ID'] ?>&IBLOCK_ID=<?= (int)$arItem['IBLOCK_ID'] ?>">
                            Редактировать
                        </a>
                    </div>
                <?php endif; ?>

            </article>
        <?php endforeach; ?>

        <footer class="news-list__footer">
            Всего новостей: <?= (int)$arResult['TOTAL_COUNT'] ?>
        </footer>

    <?php endif; ?>

</div>
