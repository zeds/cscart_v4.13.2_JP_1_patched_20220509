<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

// Modified for Japanese version by tommy from cs-cart.jp 2017
// アドオンに対応するCS-Cartのバージョンチェックの際に、日本語版のバージョン体系に対応
// エディションチェックのエラーメッセージに表示するエディション名を日本語化

namespace Tygh\Addons;

use Tygh\Domain\SoftwareProduct\Version;

class XmlScheme2 extends AXmlScheme
{
    /**
     * Performs strict version conformity check against version that has service pack.
     */
    const VALIDATE_STRICT = 'strict';

    /**
     * Allows to treat service packs as releases when performing version conformity check.
     */
    const VALIDATE_LOOSE = 'loose';

    protected static $_required_pref_scheme = array(
        'core_version' => array(
            'type' => 'version',
            'value' => PRODUCT_VERSION,
            'validation_policy' => self::VALIDATE_LOOSE,
        ),
        'core_edition' => array(
            'type' => 'enum',
            'value' => PRODUCT_EDITION,
        ),
        'php_version' => array(
            'type' => 'version',
            'value' => CS_PHP_VERSION,
            'validation_policy' => self::VALIDATE_STRICT,
        ),
        'extensions' => array(
            'type' => 'list',
        ),
    );

    public function getSections()
    {
        $sections = array();
        if (isset($this->_xml->settings->sections->section)) {
            foreach ($this->_xml->settings->sections->section as $section) {
                $_section = array(
                    'id' => (string) $section['id'],
                    'name' => (string) $section->name,
                    'translations' => $this->_getTranslations($section),
                    'edition_type' => $this->_getEditionType($section),
                    'original' => '',
                );

                if (!empty($section['outside_of_form'])) {
                    $_section['separate'] = true;
                }

                $sections[] = $_section;
            }
        }

        return $sections;
    }

    public function getSettings($section_id)
    {
        $settings = array();

        $section = $this->_xml->xpath("//section[@id='$section_id']");

        if (!empty($section) && is_array($section)) {
            $section = current($section);

            if (isset($section->items->item)) {
                foreach ($section->items->item as $setting) {
                    $settings[] = $this->_getSettingItem($setting);
                }
            }
        }

        return $settings;
    }

    public function getSettingsLayout()
    {
        return isset($this->_xml->settings['layout']) ? (string) $this->_xml->settings['layout'] : parent::getSettingsLayout();
    }

    protected function getQueries($mode = '')
    {
        $edition = PRODUCT_EDITION;

        if (empty($mode) || $mode == 'install') {
            return $this->_xml->xpath("//queries/*[(@for='install' or not(@for)) and (contains(@editions, '{$edition}') or not(@editions))]");
        } else {
            return $this->_xml->xpath("//queries/*[@for='" . $mode . "' and (contains(@editions, '{$edition}') or not(@editions))]");
        }
    }

    protected function _getLangVarsSectionName()
    {
        return '//language_variables';
    }

    /**
     * Collects information about required versions
     *
     * Example:
     *  node:
     *      <json>
     *          <min>1.0.1</min>
     *          <max>1.2.1</max>
     *      </json>
     *
     *  result:
     *      array(
     *          'min' => 1.0.1,
     *          'max' => 1.2.1,
     *      )
     *
     * @param  \SimpleXMLElement $xml_object XML note with version information. Min/Max parameters are optional
     * @return array     min and max versions
     */
    protected function _getMinMaxValues($xml_object)
    {
        $values = array();

        if (isset($xml_object->min)) {
            $values['min'] = trim((string) $xml_object->min);
        }

        if (isset($xml_object->max)) {
            $values['max'] = trim((string) $xml_object->max);
        }

        return $values;
    }

    /**
     * Checks if PHP extension is installed on server
     *
     * @param  string $extension_id Extension ID (like: "json", "calendar", "pdo")
     * @return bool   true if installed, false otherwise
     */
    protected function _checkExtensionSupporting($extension_id)
    {
        static $loaded_exts = array();

        if (empty($loaded_exts)) {
            $loaded_exts = get_loaded_extensions();
        }

        return in_array($extension_id, $loaded_exts);
    }

    /**
     * Checks if specified version in defined limits
     * Example:
     *  $version = 4.0.3
     *  $limits = array(
     *      'min' => 4.0,
     *      'max' => 5.2.1
     *  )
     *  return true
     *
     *  $version = 5.5
     *  $limits = array(
     *      'max' => 5.4.0
     *  )
     *  return false
     *
     *  $version = 4.5.1.SP3
     *  $limits = array(
     *      'max' => 4.5.1.SP2
     *  )
     *  return false
     *
     *  $version = 4.5.1.SP3
     *  $limits = array(
     *      'max' => 4.5.1
     *  )
     *  $validation_policy = self::VALIDATE_STRICT
     *  return false
     *
     *  return false
     *
     *  $version = 4.5.1.SP3
     *  $limits = array(
     *      'max' => 4.5.1
     *  )
     *  $validation_policy = self::VALIDATE_LOOSE
     *  return true
     *
     *
     * @param  string $version           Current version
     * @param  array  $limits            Version limits. min/max - version number
     * @param string  $validation_policy Specifies how check has to be performed when checking service packs
     *
     * @return bool   true if Specfied version conformity to limits
     */
    protected function _checkVersionsConformity($version, $limits, $validation_policy = self::VALIDATE_STRICT)
    {
        ////////////////////////////////////////////////////////////////////
        // Modified for Japanese version by tommy from cs-cart.jp 2017 BOF
        // 日本語版CS-Cartのメインバージョンをセット
        ////////////////////////////////////////////////////////////////////
        $jp_version_arr = explode('_JP_', $version);
        $version = new Version($jp_version_arr[0]);
        ////////////////////////////////////////////////////////////////////
        // Modified for Japanese version by tommy from cs-cart.jp 2017 EOF
        ////////////////////////////////////////////////////////////////////

        if (isset($limits['min'])) {
            if ($version->lowerThan(new Version($limits['min']))) {
                return false;
            }
        }

        if (isset($limits['max'])) {
            $max = new Version($limits['max']);
            if ($validation_policy == self::VALIDATE_LOOSE && $version->getServicePack() && !$max->getServicePack()) {
                $version = new Version($version->getRelease());
            }
            if ($version->greaterThan($max)) {
                return false;
            }
        }

        return true;
    }

    public function getDefaultLanguage()
    {
        return isset($this->_xml->default_language) ? (string) $this->_xml->default_language : DEFAULT_LANGUAGE;
    }

    public function getDependencies()
    {
        return (isset($this->_xml->compatibility->dependencies)) ? explode(',', (string) $this->_xml->compatibility->dependencies) : array();
    }

    public function getConflicts()
    {
        return (isset($this->_xml->compatibility->conflicts)) ? explode(',', (string) $this->_xml->compatibility->conflicts) : array();
    }

    /**
     * Gets addon requirements list
     *
     * @return array List of requirements
     */
    public function getRequirements()
    {
        if (empty($this->_xml->compatibility)) {
            return array();
        } else {
            $compatibility = $this->_xml->compatibility;
        }

        $requirements = array();

        if (isset($compatibility->core_version)) {
            $requirements['core_version'] = $this->_getMinMaxValues($compatibility->core_version);
        }

        if (isset($compatibility->core_edition)) {
            $requirements['core_edition'] = trim((string) $compatibility->core_edition);
        }

        if (isset($compatibility->php_version)) {
            $requirements['php_version'] = $this->_getMinMaxValues($compatibility->php_version);
        }

        if (isset($compatibility->php_extensions)) {
            foreach ((array) $compatibility->php_extensions as $name => $extension) {
                $requirements['extensions'][$name] = $this->_getMinMaxValues($extension);

                if (isset($extension->supported)) {
                    $requirements['extensions'][$name]['supported'] = trim((string) $extension->supported);
                }

                if (empty($requirements['extensions'][$name])) {
                    unset($requirements['extensions'][$name]);
                }
            }
        }

        return $requirements;
    }

    /**
     * Checks if current system preferences are suitable for the add-on
     *
     * @param  array $requirements List of add-on requirements
     * @return bool  true if all requirements are suitable
     */
    public function checkRequirements($requirements)
    {
        foreach (XmlScheme2::$_required_pref_scheme as $variable_name => $scheme) {
            if (!empty($requirements[$variable_name])) {
                switch ($scheme['type']) {
                    case 'version':
                        $version_suitable = $this->_checkVersionsConformity($scheme['value'], $requirements[$variable_name], $scheme['validation_policy']);
                        if (!$version_suitable) {
                            $min = empty($requirements[$variable_name]['min']) ? '&infin;' : $requirements[$variable_name]['min'];
                            $max = empty($requirements[$variable_name]['max']) ? '&infin;' : $requirements[$variable_name]['max'];

                            fn_set_notification('E', __('error'), __('checking_' . $variable_name . '_is_not_suitable', array(
                                '[version]' => $scheme['value'],
                                '[min]' => $min,
                                '[max]' => $max,
                            )));

                            return false;
                        }

                        break;

                    case 'text':
                        if ($scheme['value'] != strtoupper($requirements[$variable_name])) {
                            fn_set_notification('E', __('error'), __('checking_' . $variable_name . '_is_not_suitable', array(
                                '[current_edition]' => $scheme['value'],
                                '[required_edition]' => strtoupper($requirements[$variable_name]),
                            )));

                            return false;
                        }

                        break;

                    case 'enum':
                        $list = explode(',', $requirements[$variable_name]);

                        if (empty($list)) {
                            return false;
                        }

                        $checking_result = false;
                        foreach ($list as $value) {
                            if ($scheme['value'] == strtoupper($value)) {
                                $checking_result = true;

                                break;
                            }
                        }

                        if (!$checking_result) {
							//////////////////////////////////////////////////////////////////////////////
                            // Modified for Japanese version by tommy from cs-cart.jp 2017 BOF
                            // エディションチェックのエラーメッセージに表示するエディション名を日本語化
                            //////////////////////////////////////////////////////////////////////////////
                            if( $scheme['value'] == 'ULTIMATE' ){
                                $current_edition_jp = __("jp_edition_standard");
                            }elseif( $scheme['value'] == 'MULTIVENDOR' ){
                                $current_edition_jp = __("jp_edition_marketplace");
                            }else{
                                $current_edition_jp = $scheme['value'];
                            }
                            if( strtoupper($requirements[$variable_name]) == 'ULTIMATE' ){
                                $required_edition_jp = __("jp_edition_standard");
                            }elseif( strtoupper($requirements[$variable_name]) == 'MULTIVENDOR' ){
                                $required_edition_jp = __("jp_edition_marketplace");
                            }else{
                                $required_edition_jp = strtoupper($requirements[$variable_name]);
                            }

                            fn_set_notification('E', __('error'), __('checking_' . $variable_name . '_is_not_suitable', array(
                                '[current_edition]' => $current_edition_jp,
                                '[required_edition]' => $required_edition_jp,
                            )));
                            //////////////////////////////////////////////////////////////////////////////
                            // Modified for Japanese version by tommy from cs-cart.jp 2017 EOF
                            //////////////////////////////////////////////////////////////////////////////
                            return false;
                        }

                        break;

                    case 'list':
                        foreach ($requirements[$variable_name] as $extension_id => $ext_requirements) {
                            if (isset($ext_requirements['supported'])) {
                                $supported = $this->_checkExtensionSupporting($extension_id);

                                if (!$supported && $ext_requirements['supported'] == 'Y') {
                                    fn_set_notification('E', __('error'), __('checking_extension_should_be_installed', array(
                                        '[extension]' => $extension_id,
                                    )));

                                    return false;

                                } elseif ($supported && $ext_requirements['supported'] == 'N') {
                                    fn_set_notification('E', __('error'), __('checking_extension_should_be_removed', array(
                                        '[extension]' => $extension_id,
                                    )));

                                    return false;
                                }
                            }

                            if (isset($ext_requirements['min']) || isset($ext_requirements['max'])) {
                                $ext_version = phpversion($extension_id);
                                $version_suitable = $this->_checkVersionsConformity($ext_version, $ext_requirements, $scheme['validation_policy']);

                                if (!$version_suitable) {
                                    $min = empty($ext_requirements['min']) ? '&infin;' : $ext_requirements['min'];
                                    $max = empty($ext_requirements['max']) ? '&infin;' : $ext_requirements['max'];

                                    fn_set_notification('E', __('error'), __('checking_extension_version_is_not_suitable', array(
                                        '[extension]' => $extension_id,
                                        '[version]' => $ext_version,
                                        '[min]' => $min,
                                        '[max]' => $max,
                                    )));

                                    return false;
                                }
                            }
                        }

                        break;
                }

            }
        }

        return true;
    }
}
