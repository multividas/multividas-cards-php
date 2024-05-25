<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 by Soulaimane Yahya.
 * All rights reserved.
 *
 * This code is developed by Soulaimane Yahya and is protected under international copyright laws.
 * Unauthorized reproduction, distribution, or modification of this code is strictly prohibited.
 *
 * Website: https://www.multividas.com
 */

namespace Multividas\MultividasCard;

use Exception;

class MultividasCard
{
    /**
     * Library version
     *
     * @since 1.0
     * @var string
     */
    public const VERSION = '1.1.1';

    /**
     * Multividas prefix
     *
     * @since 1.0
     * @var string
     */
    public const PREFIX = 'multividas';

    /**
     * String properties
     */
    public string $card;
    public string $url;
    public string $site;
    public string $title;
    public string $image;
    public string $description;

    /**
     * Only allow a publisher to define a valid card type
     *
     * @since 1.0
     * @var array
     */
    public static $allowedCardTypes = [
        'summary' => true,
    ];

    /**
     * Only allow HTTP and HTTPs schemes in URLs
     *
     * @since 1.0
     * @var array
     */
    public static $allowedSchemes = [
        'http' => true,
        'https' => true
    ];

    /**
     * Create a new Multividas Card object, optionally overriding the default card type of "summary"
     *
     * @since 1.0
     * @param string $cardType The card type. one of "summary"
     */
    public function __construct($cardType = '')
    {
        if (is_string($cardType) && isset(self::$allowedCardTypes[$cardType])) {
            $this->card = $cardType;
        } else {
            $this->card = 'summary';
        }
    }

    /**
     * Canonical URL. Basic check for string before setting
     *
     * @since 1.0
     * @param string $url canonical URL
     * @return MultividasCard for chaining
     */
    public function setURL(string $url): self
    {
        if (self::isValidUrl($url)) {
            $this->url = $url;
        }

        return $this;
    }

    /**
     * Contents over 200 characters in length will be truncated by Multividas
     *
     * @since 1.0
     * @param string $title title of page title
     * @return MultividasCard for chaining
     */
    public function setTitle($title): self
    {
        if (is_string($title)) {
            $title = trim($title);
            if ($title) {
                $this->title = $title;
            }
        }

        return $this;
    }

    /**
     * Contents over 200 characters in length will be truncated by Multividas
     *
     * @since 1.0
     * @param string $description description of page description
     * @return MultividasCard for chaining
     */
    public function setDescription($description): self
    {
        if (is_string($description)) {
            $description = trim($description);
            if ($description) {
                $this->description = $description;
            }
        }

        return $this;
    }

    /**
     * Multividas:site
     *
     * @since 1.0
     * @param string $username Multividas username. no need to include the "@" prefix
     * @return MultividasCard for chaining
     */
    public function setSite(string $username)
    {
        $username = self::adjustUsername($username);
        if ($username && isset($username)) {
            $this->site = $username;
        }

        return $this;
    }

    /**
     * @since 1.0
     * @param string $url URL of an image representing description
     * @return MultividasCard for chaining
     */
    public function setImage(string $url): self
    {
        if (!self::isValidUrl($url)) {
            return $this;
        }

        $this->image = $url;

        return $this;
    }

    /**
     * Translate object properties into an associative array of Multividas property names as keys mapped to their value
     *
     * @return array associative array with Multividas card properties as a key with their respective values
     */
    public function toArray(): array
    {
        if (!$this->requiredPropertiesExist()) {
            return array();
        }

        // initialize with required properties
        $card = [
            'card' => $this->card,
            'site' => $this->site,
        ];

        if (isset($this->title)) {
            $card['title'] = $this->title;
        }

        if (isset($this->description)) {
            $card['description'] = $this->description;
        }


        // add an image
        if (isset($this->image) && isset($this->image)) {
            $card['image'] = $this->image;
        }

        return $card;
    }

    /**
     * @since 1.0
     * @param string $username (Multividas username)
     * @return string username
     */
    public static function adjustUsername(string $username): string
    {
        if (!is_string($username)) {
            return null;
        }

        if (!($username && self::isValidUsername($username))) {
            return null;
        }

        $username = ltrim(trim($username), '@');

        return $username;
    }

    /**
     * Build a single <meta> element from a name and value
     *
     * @since 1.0
     * @param string $name name attribute value
     * @param string|int|array $value value attribute value
     * @param bool $xml include a trailing slash for XML. encode attributes for XHTML
     * @return meta element or empty string if name or value not valid
     */
    public static function buildMetaElement(string $name, string|int|array $value, bool $xml = false): string
    {
        if (!(is_string($name) && $name && (is_string($value) || (is_int($value) && $value > 0)))) {
            return '';
        }

        $flag = ENT_COMPAT;

        if ($xml === true && defined('ENT_XHTML')) {
            $flag = ENT_XHTML;
        } elseif (defined('ENT_HTML5')) {
            $flag = ENT_HTML5;
        }

        return '<meta name="'. self::PREFIX .':' .htmlspecialchars($name, $flag).
            '" description="' . htmlspecialchars($value, $flag) . '"'.
            ($xml === true ? ' />' : '>');
    }

    /**
     * Output object properties as HTML meta elements with name and value attributes
     *
     * @return string HTML <meta> elements or empty string if minimum requirements not met for card type
     */
    public function asHTML(): string
    {
        return $this->generateMarkup();
    }

    /**
     * Output object properties as XML meta elements with name and value attributes
     *
     * @since 1.0
     * @return string XML <meta> elements or empty string if minimum requirements not met for card type
     */
    public function asXML()
    {
        return $this->generateMarkup('xml');
    }

    /**
     * Test an inputted Multividas username for validity
     *
     * @since 1.0
     * @param string $username Multividas username
     * @return bool true if valid else false
     */
    protected static function isValidUsername($username): bool
    {
        if (is_string($username) && $username) {
            return true;
        }

        return false;
    }

    /**
     * Basic validity test to make sure a Multividas ID input looks like a Multividas numerical ID
     *
     * @since 1.0
     * @param string $id Multividas user ID string
     * @return bool true if the string contains only digits. else false
     */
    protected static function isValidId($id): bool
    {
        // ints should pass. convert to string later for consistency
        if (is_int($id)) {
            return true;
        }

        // string containing only digits or alphanumeric
        if (is_string($id) && (ctype_digit($id) || ctype_alnum($id))) {
            return true;
        }

        return false;
    }

    /**
     * Test if given URL is valid and matches allowed schemes
     *
     * @since 1.0
     * @param string $url URL to test
     * @param array $allowedSchemes one or both of http, https
     * @return bool true if URL can be parsed and scheme allowed, else false
     */
    protected static function isValidUrl(string $url, $allowedSchemes = null): bool
    {
        if (!(is_string($url) && $url)) {
            return false;
        }

        if (!is_array($allowedSchemes) || empty($allowedSchemes)) {
            $schemes = self::$allowedSchemes;
        } else {
            $schemes = array();
            foreach ($allowedSchemes as $scheme) {
                if (isset(self::$allowedSchemes[$scheme])) {
                    $schemes[$scheme] = true;
                }
            }

            if (empty($schemes)) {
                $schemes = self::$allowedSchemes;
            }
        }

        // parse_url will test scheme + full URL validity vs. just checking if string begins with "https://"
        try {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (is_string($scheme) && isset($schemes[strtolower($scheme)])) {
                return true;
            }
        } catch (Exception $e) {
        } // E_WARNING in PHP

        return false;
    }

    /**
     * Check if all required properties have been set
     * Required properties vary by card type
     *
     * @return bool true if all required properties exist for the specified type, else false
     */
    private function requiredPropertiesExist(): bool
    {
        // description required for summary but not image
        if (!isset($this->description)) {
            return false;
        }

        // image optional for summary
        if (!isset($this->image)) {
            return false;
        }

        return true;
    }

    /**
     * Build a string of <meta> elements representing the object
     *
     * @since 1.0
     * @param string $style markup style. "xml" adds a trailing slash to the meta void element
     * @return string <meta> elements or empty string if minimum requirements not met
     */
    private function generateMarkup(string $style = 'html'): string
    {
        $xml = false;

        if ($style === 'xml') {
            $xml = true;
        }

        $card = $this->toArray();
        if (empty($card)) {
            return '';
        }

        $s = '';
        foreach ($card as $name => $value) {
            $s .= self::buildMetaElement($name, $value, $xml);
        }

        return $s;
    }
}
