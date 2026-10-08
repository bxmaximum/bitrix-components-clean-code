<?php

declare(strict_types=1);

namespace Project\Core\Traits;

trait WithSeo
{
    protected function setSeo(string $title, string $description = ''): void
    {
        global $APPLICATION;

        $APPLICATION->SetTitle($title);

        if ($description !== '') {
            $APPLICATION->SetPageProperty('description', $description);
        }
    }

    protected function setOgTags(string $title, string $description, string $image = ''): void
    {
        global $APPLICATION;

        $APPLICATION->SetPageProperty('og:title', $title);
        $APPLICATION->SetPageProperty('og:description', $description);
        $APPLICATION->SetPageProperty('og:type', 'article');

        if ($image !== '') {
            $APPLICATION->SetPageProperty('og:image', $image);
        }
    }

    protected function addBreadcrumb(string $title, string $link = ''): void
    {
        global $APPLICATION;
        $APPLICATION->AddChainItem($title, $link);
    }
}
