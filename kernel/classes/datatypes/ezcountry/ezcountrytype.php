<?php
/**
 * File containing the eZCountryType class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package kernel
 */

/**
 * A content datatype that contains countries.
 *
 * @package kernel
 */
class eZCountryType extends eZDataType
{
    const DATA_TYPE_STRING = 'ezcountry';

    const DEFAULT_LIST_FIELD = 'data_text5';

    const MULTIPLE_CHOICE_FIELD = 'data_int1';

    public function __construct()
    {
        parent::__construct( self::DATA_TYPE_STRING, ezpI18n::tr( 'kernel/classes/datatypes', 'Country', 'Datatype name' ),
                           array( 'serialize_supported' => true,
                                  'object_serialize_map' => array( 'data_text' => 'country' ) ) );
    }

    /**
     * Fetches a list of countryies list from country.ini
     *
     * @return array
     */
    static function fetchCountryList()
    {
        if ( isset( $GLOBALS['CountryList'] ) )
            return $GLOBALS['CountryList'];

        $ini = eZINI::instance( 'country.ini' );
        $countries = $ini->getNamedArray();
        eZCountryType::fetchTranslatedNames( $countries );
        $GLOBALS['CountryList'] = $countries;
        return $countries;
    }

    /**
     * Fetches translated country names from locale. $countries will be updated.
     *
     * @param $countries
     */
    static function fetchTranslatedNames( &$countries )
    {
        $locale = eZLocale::instance();
        $translatedCountryNames = $locale->translatedCountryNames();
        foreach ( array_keys( $countries ) as $countryKey )
        {
            $translatedName = isset( $translatedCountryNames[$countryKey] ) ? $translatedCountryNames[$countryKey] : false;
            if ( $translatedName )
                $countries[$countryKey]['Name'] = $translatedName;
        }
        usort( $countries, array( 'eZCountryType', 'compareCountryNames' ) );
    }

    /**
     * Sort callback used by fetchTranslatedNames to compare two country arrays
     *
     * @param array $a Country 1
     * @param array $b Country 2
     * @return bool
     */
    protected static function compareCountryNames( $a, $b )
    {
        return strcoll( $a["Name"], $b["Name"] );
    }

    /**
     * Fetches country by $fetchBy. If $fetchBy is false country name will be used.
     *
     * @param string $value
     * @param string|bool $fetchBy
     * @return bool
     */
    static function fetchCountry( $value, $fetchBy = false )
    {
        $fetchBy = !$fetchBy ? 'Name' : $fetchBy;

        $allCountries = eZCountryType::fetchCountryList();
        $result = false;
        if ( $fetchBy == 'Alpha2' and isset( $allCountries[strtoupper( $value )] ) )
        {
            $result = $allCountries[$value];
            return $result;
        }

        foreach ( $allCountries as $country )
        {
            if ( isset( $country[$fetchBy] ) and $country[$fetchBy] == $value )
            {
                $result = $country;
                break;
            }
        }

        return $result;
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        $classAttributeID = $classAttribute->attribute( 'id' );
        $content = $classAttribute->content();

        if ( $http->hasPostVariable( $base . '_ezcountry_multiple_choice_value_' . $classAttribute->attribute( 'id' ) . '_exists' ) )
        {
             $content['multiple_choice'] = $http->hasPostVariable( $base . "_ezcountry_ismultiple_value_" . $classAttributeID ) ? 1 : 0;
        }

        if ( $http->hasPostVariable( $base . '_ezcountry_default_selection_value_' . $classAttribute->attribute( 'id' ) . '_exists' ) )
        {
            if ( $http->hasPostVariable( $base . "_ezcountry_default_country_list_". $classAttributeID ) )
            {
                $defaultValues = $http->postVariable( $base . "_ezcountry_default_country_list_". $classAttributeID );
                $defaultList = array();
                foreach ( $defaultValues as $alpha2 )
                {
                    if ( trim( $alpha2 ) == '' )
                        continue;
                    // Fetch ezcountry by aplha2 code (as reserved in iso-3166 code list)
                    $eZCountry = eZCountryType::fetchCountry( $alpha2, 'Alpha2' );
                    if ( $eZCountry )
                        $defaultList[$alpha2] = $eZCountry;
                }
                $content['default_countries'] = $defaultList;
            }
            else
            {
                $content['default_countries'] = array();
            }
        }
        $classAttribute->setContent( $content );
        $classAttribute->store();
        return true;
    }

    function preStoreClassAttribute( $classAttribute, $version )
    {
        $content = $classAttribute->content();
        return eZCountryType::storeClassAttributeContent( $classAttribute, $content );
    }

    function storeClassAttributeContent( $classAttribute, $content )
    {
        if ( is_array( $content ) )
        {
            $multipleChoice = $content['multiple_choice'];
            $defaultCountryList = $content['default_countries'];
            $defaultCountry = implode( ',', array_keys( $defaultCountryList ) );

            $classAttribute->setAttribute( self::DEFAULT_LIST_FIELD, $defaultCountry );
            $classAttribute->setAttribute( self::MULTIPLE_CHOICE_FIELD, $multipleChoice );
        }
        return false;
    }

    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $dataText = $originalContentObjectAttribute->content();
            $contentObjectAttribute->setContent( $dataText );
        }
        else
        {
            $default = array( 'value' => array() );
            $contentObjectAttribute->setContent( $default );
        }
    }

    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( !$contentObjectAttribute->validateIsRequired() )
            return eZInputValidator::STATE_ACCEPTED;

        if ( $http->hasPostVariable( $base . '_country_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_country_' . $contentObjectAttribute->attribute( 'id' ) );

            if ( count( $data ) > 0 and $data[0] != '' )
                return eZInputValidator::STATE_ACCEPTED;
        }

        $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                             'Input required.' ) );
        return eZInputValidator::STATE_INVALID;
    }

    function validateCollectionAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( !$contentObjectAttribute->validateIsRequired() )
            return eZInputValidator::STATE_ACCEPTED;

        if ( $http->hasPostVariable( $base . '_country_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_country_' . $contentObjectAttribute->attribute( 'id' ) );

            if ( count( $data ) > 0 and $data[0] != '' )
                return eZInputValidator::STATE_ACCEPTED;
        }

        $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                                                             'Input required.' ) );
        return eZInputValidator::STATE_INVALID;
    }

    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_country_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_country_' . $contentObjectAttribute->attribute( 'id' ) );
            $defaultList = array();
            if ( is_array( $data ) )
            {
                foreach ( $data as $alpha2 )
                {
                    if ( trim( $alpha2 ) == '' )
                        continue;

                    $eZCountry = eZCountryType::fetchCountry( $alpha2, 'Alpha2' );
                    if ( $eZCountry )
                        $defaultList[$alpha2] = $eZCountry;
                }
            }
            else
            {
                $countries = eZCountryType::fetchCountryList();
                foreach ( $countries as $country )
                {
                    if ( $country['Name'] == $data )
                    {
                        $defaultList[$country['Alpha2']] = $country['Name'];
                    }
                }
            }
            $content = array( 'value' => $defaultList );

            $contentObjectAttribute->setContent( $content );
        }
        else
        {
            $content = array( 'value' => array() );
            $contentObjectAttribute->setContent( $content );
        }
        return true;
    }

    function fetchCollectionAttributeHTTPInput( $collection, $collectionAttribute, $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . "_country_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $dataText = $http->postVariable( $base . "_country_" . $contentObjectAttribute->attribute( "id" ) );

            $value = implode( ',', $dataText );
            $collectionAttribute->setAttribute( 'data_text', $value );
            return true;
        }
        return false;
    }

    function storeObjectAttribute( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();

        $valueArray = $content['value'];
        $value = is_array( $valueArray ) ? implode( ',', array_keys( $valueArray ) ) : $valueArray;

        $contentObjectAttribute->setAttribute( "data_text", $value );
    }

    function isSimpleStringInsertionSupported()
    {
        return true;
    }

    function insertSimpleString( $object, $objectVersion, $objectLanguage,
                                 $objectAttribute, $string,
                                 &$result )
    {
        $result = array( 'errors' => array(),
                         'require_storage' => true );
        $content = array( 'value' => $string );
        $objectAttribute->setContent( $content );
        return true;
    }

    /**
     * @inheritdoc
     * @return array
     */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $value = $contentObjectAttribute->attribute( 'data_text' );

        $countryList = explode( ',', $value );
        $resultList = array();
        foreach ( $countryList as $alpha2 )
        {
            $eZCountry = eZCountryType::fetchCountry( $alpha2, 'Alpha2' );
            $resultList[$alpha2] = $eZCountry ? $eZCountry : '';
        }
        // Supporting of previous version format.
        // For backwards compatibility.
        if ( count( $resultList ) == 1 and $resultList[$value] == '' )
            $resultList = $value;

        $content = array( 'value' => $resultList );
        return $content;
    }

    /**
     * @inheritdoc
     * @return array
     */
    function classAttributeContent( $classAttribute )
    {
        $defaultCountry = $classAttribute->attribute( self::DEFAULT_LIST_FIELD );
        $multipleChoice = $classAttribute->attribute( self::MULTIPLE_CHOICE_FIELD );
        $defaultCountryList = explode( ',', $defaultCountry );
        $resultList = array();
        foreach ( $defaultCountryList as $alpha2 )
        {
            $eZCountry = eZCountryType::fetchCountry( $alpha2, 'Alpha2' );
            if ( $eZCountry )
                $resultList[$alpha2] = $eZCountry;
        }
        $content = array( 'default_countries' => $resultList,
                          'multiple_choice' => $multipleChoice );

        return $content;
    }

    function metaData( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        if ( is_array( $content['value'] ) )
        {
            $imploded = '';
            foreach ( $content['value'] as $country )
            {
                $countryName = $country['Name'];
                if ( $imploded == '' )
                    $imploded = $countryName;
                else
                    $imploded .= ',' . $countryName;
            }
            $content['value'] = $imploded;
        }
        return $content['value'];
    }

    function toString( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }

    function fromString( $contentObjectAttribute, $string )
    {
        return $contentObjectAttribute->setAttribute( 'data_text', $string );
    }

    function title( $contentObjectAttribute, $name = null )
    {
        $content = $contentObjectAttribute->content();
        if ( is_array( $content['value'] ) )
        {
            $imploded = '';
            foreach ( $content['value'] as $country )
            {
                $countryName = $country['Name'];
                if ( $imploded == '' )
                    $imploded = $countryName;
                else
                    $imploded .= ',' . $countryName;
            }
            $content['value'] = $imploded;
        }
        return $content['value'];
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        $result = ( ( !is_array( $content['value'] ) and trim( $content['value'] ) != '' ) or ( is_array( $content['value'] ) and count( $content['value'] ) > 0 ) );
        return $result;
    }

    function isIndexable()
    {
        return true;
    }

    function isInformationCollector()
    {
        return true;
    }

    function sortKey( $contentObjectAttribute )
    {
        $trans = eZCharTransform::instance();
        $content = $contentObjectAttribute->content();
        if ( is_array( $content['value'] ) )
        {
            $imploded = '';
            foreach ( $content['value'] as $country )
            {
                $countryName = $country['Name'];

                if ( $imploded == '' )
                    $imploded = $countryName;
                else
                    $imploded .= ',' . $countryName;
            }
            $content['value'] = $imploded;
        }
        return $trans->transformByGroup( $content['value'], 'lowercase' );
    }

    function sortKeyType()
    {
        return 'string';
    }

    function diff( $old, $new, $options = false )
    {
        return null;
    }

    function supportsBatchInitializeObjectAttribute()
    {
        return true;
    }
}

eZDataType::register( eZCountryType::DATA_TYPE_STRING, 'ezcountrytype' );

?>
