<?php

namespace Oksydan\Module\IsThemeCore\Core\Smarty;

use Oksydan\Module\IsThemeCore\Form\Settings\WebpConfiguration;
use Oksydan\Module\IsThemeCore\Core\Webp\WebpPictureGenerator;

class SmartyHelperFunctions {

    public static function generateImagesSources($params) {
      $image = $params['image'];
      $size = $params['size'];
      $lazyLoad = isset($params['lazyload']) ? $params['lazyload'] : true;
      $attributes = [];
      $highDpiImagesEnabled = (bool) \Configuration::get('PS_HIGHT_DPI');

      $img = $image['bySize'][$size]['url'];

      if ($highDpiImagesEnabled) {
        $size2x = $size . '2x';
        $img2x = str_replace($size, $size2x, $img);
        $attributes['srcset'] = "$img, $img2x 2x";
      } else {
        $attributes['src'] = $img;
      }

      if ($lazyLoad) {
        $attributes['loading'] = 'lazy';
      }

      $attributesToPrint = [];

      foreach ($attributes as $attr => $value) {
        $attributesToPrint[] = $attr . '="' . $value . '"';
      }

      return implode($attributesToPrint, PHP_EOL);
    }

    public static function generateImageSvgPlaceholder($params) {
      $width = $params['width'];
      $height = $params['height'];

      return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='$width' height='$height' viewBox='0 0 1 1'%3E%3C/svg%3E";
    }

    public static function appendParamToUrl($params) {
      list(
        'url' => $url,
        'key' => $key,
        'value' => $value
      ) = $params;

      $query = parse_url($url, PHP_URL_QUERY);

      if ($query) {
        parse_str($query, $queryParams);
        $queryParams[$key] = $value;
        $url = str_replace("?$query", '?' . http_build_query($queryParams), $url);
      } else {
        $url .= '?' . urlencode($key) . '=' . urlencode($value);
      }

      return $url;
    }

    public static function imagesBlock($params, $content, $smarty)
    {
      $webpEnabled = isset($params['webpEnabled']) ? $params['webpEnabled'] : \Configuration::get(WebpConfiguration::THEMECORE_WEBP_ENABLED);

      if ($webpEnabled && !empty($content)) {
        $pictureGenerator = new WebpPictureGenerator($content);

        $pictureGenerator
          ->loadContent()
          ->generatePictureTags();

        return $pictureGenerator->getContent();
      }

      return $content;
    }

    public static function cmsImagesBlock($params, $content, $smarty)
    {
      $doc = new \DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8">' . $content);

      $images = $doc->getElementsByTagName('img');

      $domains = \Tools::getDomains();
      $medias = [
        \Configuration::get('PS_MEDIA_SERVER_1'),
        \Configuration::get('PS_MEDIA_SERVER_2'),
        \Configuration::get('PS_MEDIA_SERVER_3'),
      ];

      $internalUrls = [];

      foreach ($domains as $domain => $options) {
        $internalUrls[] = $domain;
      }

      foreach ($medias as $media) {
        if ($media) {
          $internalUrls[] = $media;
        }
      }

      foreach ($images as $image) {
        $newImg = $doc->createElement('img');
        $src = urldecode($image->attributes->getNamedItem('src')->nodeValue);

        if (!preg_match('/' . implode('|', $internalUrls) . '/i', $src)) {
          $newImg->setAttribute('data-external-url', '');
        }

        foreach ($image->attributes as $attribute) {
          $newImg->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }

        $image->parentNode->replaceChild($newImg, $image);
      }

      $content = $doc->saveHTML();
      $content = str_replace('<?xml encoding="utf-8" ?>', '', $content);

      $webpEnabled = isset($params['webpEnabled']) ? $params['webpEnabled'] : \Configuration::get(WebpConfiguration::THEMECORE_WEBP_ENABLED);

      if ($webpEnabled && !empty($content)) {
        $pictureGenerator = new WebpPictureGenerator($content);

        $content = $doc->saveHTML();
        $content = str_replace('<meta http-equiv="Content-Type" content="charset=utf-8">', '', $content);

        return $pictureGenerator->getContent();
      }

      return $content;
    }
}
