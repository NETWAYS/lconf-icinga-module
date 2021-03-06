<?php

class LConf_LDAPHelperModel extends IcingaLConfBaseModel {

    static public function formatToPropertyArray(array $arr) {
        $returnArray = array();
        foreach ($arr as $attribute => $value) {
            if (!is_array($value))
                continue;
            if (!isset($value["count"]))
                $value["count"] = 1;
            $valueCount = $value["count"];

            if ($valueCount == 1) {
                $returnArray[$attribute] = $value[0];
            } else {
                $returnArray[$attribute] = array();
                for ($i = 0; $i < $valueCount; $i++) {
                    $returnArray[$attribute][] = $value[$i];
                }
            }
            if (is_array($returnArray[$attribute]) && isset($returnArray[$attribute]["value"]))
                $returnArray[$attribute] = $returnArray[$attribute]["value"];
        }
        return $returnArray;
    }

    /**
     * Escape string according to
     * http://tools.ietf.org/html/rfc4514#section-4
     * @param unknown_type $str
     */
    static public function escapeString($str) {
        if ($str[0] == '#' || $str[0] == ' ')
            $str = substr($str, 1);
        if ($str[strlen($str) - 1] == ' ')
            $str = substr($str, 0, -1);
        $replacements = array(
            "," => "\\2C",
            "+" => "\\2B",
            ";" => "\\3B",
            "<" => "\\3C",
            "=" => "\\3D",
            ">" => "\\3E",
            '"' => "\\22"
        );

        foreach ($replacements as $old => $new) {
            $str = str_replace($old, $new, $str);
        }
        return $str;
    }

    /**
     * Quote a string that should be used in a DN
     *
     * Special characters will be escaped
     *
     * @param  string DN-component
     * @return string
     */
    public static function quoteForDN($str)
    {
        return self::quoteChars($str, array(
            ',', '=', '+', '<', '>', ';', '\\', '"', '#'
        ));
    }

    /**
     * Quote a string that should be used in an LDAP search
     *
     * Special characters will be escaped
     *
     * @param  string String to be escaped
     * @return string
     */
    public static function quoteForSearch($str)
    {
        return self::quoteChars($str, array('*', '(', ')', '\\', chr(0)));
    }

    /**
     * Escape given characters in the given string
     *
     * Special characters will be escaped
     *
     * @param  string String to be escaped
     * @return string
     */
    public static function quoteChars($str, $chars)
    {
        $quotedChars = array();
        foreach ($chars as $k => $v) {
            $quotedChars[$k] = ',' . str_pad(dechex(ord($v)), 2, '0');
        }
        $str = str_replace($chars, $quotedChars, $str);
        // Workaround, str_replace behaves pretty strange with leading backslash
        $str = preg_replace('~,~', '\\', $str);
        return $str;
    }

    /**
     * Method that tries to guess the baseDN if none is set in the options
     * @param string $dn
     * @return string
     */
    static public function suggestBaseDNFromName($dn) {
        $explodedDN = ldap_explode_dn($dn, 0);
        $dn = "";
        foreach ($explodedDN as $val) {
            $splitted = explode("=", $val);
            if ($splitted[0] == "dc") {
                $dn .= ($dn ? "," : "") . $val;
            }
        }
        return $dn;
    }

    static public function cleanResult(&$result, $firstLevel = true) {
        if (!is_array($result))
            return false;
        if (isset($result["count"]))
            unset($result["count"]);

        // recursively clean array
        foreach ($result as $name => &$elem) {
            if (is_array($elem) && !empty($elem)) {
                self::cleanResult($elem, false);
            }
        }
        // remove reference garbage and reset pointer 
        unset($elem);
        reset($result);

        //  reset firstlevel values (aren't needed)
        if ($firstLevel) {
            foreach ($result as $key => $name) {
                if (!is_array($name)) {
                    unset($result[$key]);
                }
            }
        }

        //remove empty values
        foreach ($result as $nr => $elem)
            if (empty($elem) && is_array($elem))
                unset($result[$nr]);
    }

    static public function resolveAliases($resultset) {
        if (!is_array($resultset))
            return $resultset;
        foreach ($resultset as &$result) {
            if (!is_array($result))
                continue;

            $isAlias = false;

            foreach ($result["objectclass"] as $type) {
                if ($type == "alias") {
                    $isAlias = true;
                    break;
                }
            }
            if (!$isAlias)
                continue;
            //    $result["aliasdn"] = $result["aliasedobjectname"];
            //$result["dn"] = "ALIAS=Alias of:".$result["aliasedobjectname"][0];
        }
        return $resultset;
    }

    static public function validateAliases($resultset, $conn, $base = "") {
        if (!is_array($resultset))
            return $resultset;
        foreach ($resultset as &$result) {
            if (!is_array($result))
                continue;

            $isAlias = false;

            foreach ($result["objectclass"] as $type) {
                if ($type == "alias") {
                    $result["valid"] = true;
                    if (!isset($result["aliasedobjectname"])) {
                        $result["valid"] = false;
                    } else {
                        $search = explode(",", $result["aliasedobjectname"][0], 2);
                        $rs = @ldap_get_entries($conn, @ldap_search($conn, $search[1], "(" . self::quoteForSearch($search[0]) . ")"));
                        if ($rs["count"] == 0)
                            $result["valid"] = false;
                    }
                    break;
                }
            }
        }
        return $resultset;
    }

    static public function filterTree($elems, array $searchresult) {
        if (!is_array($elems))
            return $elems;
        $toDelete = array();

        foreach ($elems as $key => &$currentElement) {
            if (!is_array($currentElement))
                continue;
            $currentElement["match"] = "noMatch";
            $foundChild = false;
            foreach ($searchresult as $result) {
                $dnToCheck = $currentElement["dn"];
                $resultDn = $result["dn"];
                // respect da alias!
                if (isset($currentElement["aliasedobjectname"])) {
                    if (isset($currentElement["aliasdn"]))
                        $dnToCheck = $currentElement["aliasdn"];
                }

                // its a simple check whether the returned dn is part of a searchdn result
                if (strlen($dnToCheck) > strlen($resultDn))
                    continue;
                if (substr($resultDn, -1 * strlen($dnToCheck)) == $dnToCheck) {
                    $foundChild = true;
                    if ($dnToCheck == $resultDn) {
                        $currentElement["match"] = "match";
                    }
                    break;
                }
            }
            if (!$foundChild)
                $toDelete[] = $key;
        }
        foreach ($toDelete as $key) {
            unset($elems[$key]);
        }
        return $elems;
    }

}

?>
