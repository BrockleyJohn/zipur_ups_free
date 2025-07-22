<?php

    
/*
* $Id: zipupsoauth.php
* $Loc: /includes/modules/shipping/
*
* Name: ZipShippingUPSoAuth
* Version: 2.1.0
* Release Date: 04/22/2024
* Author: Preston Lord
* 	 phoenixaddons.com / @zipurman / plord@inetx.ca
*
* License: Released under the GNU General Public License
*
* Comments: Copyright (c) 2024: Preston Lord - @zipurman - Intricate Networks Inc.
* 
* 
*   Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
* 
*   1. Re-distributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* 
*   2. Re-distributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* 
*   3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
* 
*   THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* (Packaged with Zipur Bundler v2.2.0)
*/



    /**
     * Class zipups
     */
    class zipupsoauth extends abstract_shipping_module {

        const CONFIG_KEY_BASE = 'MODULE_SHIPPING_ZIP_UPS_OATH_';

        private $shipment_details = [];
        private $api_connection = [];
        private $shipment_extra_insurance = 0;

        const API_HOST_AUTH_TEST_URL = 'https://wwwcie.ups.com/security/v1/oauth/';
        const API_HOST_AUTH_PROD_URL = 'https://onlinetools.ups.com/security/v1/oauth/';

        const API_HOST_RATE_TEST_URL = 'https://wwwcie.ups.com/api/rating/v1/Shop';//Rate would return only selected
        const API_HOST_RATE_PROD_URL = 'https://onlinetools.ups.com/api/rating/v1/Shop';

        public function __construct() {

            global $order;

            parent::__construct();

            $this->quotes[ 'methods' ] = [];

            if ( !empty( $order ) ) {

                $this->setApiHostUrl();
                $this->api_connection [ 'api_token' ] = $this->getAPIToken( MODULE_SHIPPING_ZIP_UPS_OATH_USER_ID, MODULE_SHIPPING_ZIP_UPS_OATH_ACCESS_KEY );

                //start shipment definition
                $this->shipment_details[ 'shipment' ] = [
                    'insure_shipment' => MODULE_SHIPPING_ZIP_UPS_OATH_INSURE == 'True',
                    'total_value'     => number_format( ceil( $order->info[ 'subtotal' ] ), 2, '.', '' ),
                    'currency'        => $order->info[ 'currency' ],
                ];
                $this->shipment_details[ 'quote' ]    = [];

                //used by abstract_shipping_module
                $this->tax_class = MODULE_SHIPPING_ZIP_UPS_OATH_TAX_CLASS;
            }
        }

        /**
         * @return void
         */
        private function setApiHostUrl() {
            if ( MODULE_SHIPPING_ZIP_UPS_OATH_MODE == 'Test' ) {
                $this->api_connection [ 'auth_host' ] = self::API_HOST_AUTH_TEST_URL;
                $this->api_connection [ 'rate_host' ] = self::API_HOST_RATE_TEST_URL;
            } else {
                $this->api_connection [ 'auth_host' ] = self::API_HOST_AUTH_PROD_URL;
                $this->api_connection [ 'rate_host' ] = self::API_HOST_RATE_PROD_URL;
            }
        }

        /**
         * @param $url
         * @param $method
         * @param $headers
         * @param $data
         * @param $json
         *
         * @return mixed
         */
        function curlRequest( $url, $method, $headers, $data = null, $json = false ) {

            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );

            if ( $data !== null && empty( $json ) ) {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
            } else if ( $data !== null && $method == 'POST' ) {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
            }

            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

            $response = curl_exec( $ch );

            curl_close( $ch );

            return json_decode( $response, true );
        }

        /**
         * @param $client_id
         * @param $client_secret
         *
         * @return mixed
         */
        function getAPIToken( $client_id, $client_secret ) {

            $data = [
                'grant_type' => 'client_credentials',
            ];

            $headers = [
                "Content-Type: application/x-www-form-urlencoded",
                "x-merchant-id: string",
                "Authorization: Basic " . base64_encode( $client_id . ':' . $client_secret )
            ];

            $accessToken = $this->curlRequest( $this->api_connection[ 'auth_host' ] . 'token', 'POST', $headers, $data );

            if ( empty( $accessToken[ 'access_token' ] ) ) {
//                var_dump( $accessToken );
            }

            return $accessToken[ 'access_token' ] ?? '';

        }

        /**
         * @param $query
         * @param $link
         *
         * @return mixed
         */
        public function db_query( $query, $link = 'db' ) {

            if ( version_compare( '1.0.8.19', $this->phoenix_version() ) > 0 ) {
                return tep_db_query( $query );
            } else {
                return $GLOBALS[ $link ]->query( $query );
            }

        }

        /**
         * @param $db_query
         *
         * @return mixed
         */
        function fetch_array( $db_query ) {

            if ( version_compare( '1.0.8.19', $this->phoenix_version() ) > 0 ) {
                return tep_db_fetch_array( $db_query );
            } else {
                return $db_query->fetch_assoc();
            }

        }

        /**
         * @param $string
         * @param $link
         *
         * @return mixed
         */
        function db_input( $string, $link = 'db' ) {

            if ( version_compare( '1.0.8.19', $this->phoenix_version() ) > 0 ) {
                return tep_db_input( $string, $link );
            } else {
                return $GLOBALS[ $link ]->real_escape_string( $string );
            }

        }

        /**
         * @param $attributes
         *
         * @return array
         */
        function normalize( $attributes ) {

            $parameters = [];
            foreach ( preg_split( '{"[^"]*"(*SKIP)(*FAIL)|\s+}', $attributes ) as $parameter ) {
                $pair = explode( '=', $parameter, 2 );
                if ( !empty( $pair[ 0 ] ) ) {
                    $parameters[ $pair[ 0 ] ] = isset( $pair[ 1 ] ) ? trim( $pair[ 1 ], '"' ) : null;
                }
            }

            return $parameters;
        }

        /**
         * @param $src
         * @param $alt
         * @param $width
         * @param $height
         * @param $parameters
         * @param $responsive
         * @param $bootstrap_css
         *
         * @return string
         */
        function image( $src, $alt = '', $width = '', $height = '', $parameters = '', $responsive = true, $bootstrap_css = '' ) {

            if ( version_compare( '1.0.8.19', $this->phoenix_version() ) > 0 ) {
                return tep_image( $src, $alt = '', $width = '', $height = '', $parameters = '', $responsive = true, $bootstrap_css = '' );
            } else {
                $image = new Image( $src, $this->normalize( $parameters ) );
                if ( defined( 'DIR_FS_ADMIN' ) ) {
                    $image->set_prefix( DIR_FS_ADMIN );
                }
                if ( !Text::is_empty( $alt ) ) {
                    $image->set( 'alt', $alt );
                }

                if ( !Text::is_empty( $width ) ) {
                    $image->set( 'width', $width );
                }

                if ( !Text::is_empty( $height ) ) {
                    $image->set( 'height', $height );
                }

                if ( $responsive !== true ) {
                    $image->set_responsive( false );
                }

                if ( !Text::is_empty( $bootstrap_css ) ) {
                    $image->append_css( $bootstrap_css );
                }

                return "$image";

            }
        }

        /**
         * @return string
         */
        function phoenix_version() {
            return trim( file_get_contents( DIR_FS_CATALOG . 'includes/version.php' ) );
        }

        /**
         * @param $shipping_method
         *
         * @return array
         */
        public function quote( $shipping_method ) {

            $this->calcWeight();
            $this->setOrigin();
            $this->setDestination();
            $this->getUPSoAuthQuote();

            //after selection - limit to selected
            if ( !empty( $shipping_method ) ) {
                foreach ( $this->quotes[ 'methods' ] as $item ) {
                    if ( $item[ 'id' ] == $shipping_method ) {
                        $this->quotes[ 'methods' ] = [ $item ];
                    }
                }
            }

            //abstract_shipping_module - abstract_zoneable_module will take care of ZONE/TAX
            $this->quote_common();

            if ( !empty( $this->quotes ) && !empty( $this->quotes[ 'methods' ] ) ) {
                return $this->quotes;
            } else {
                //added for pre-login freight estimators
                $this->quotes[ 'methods' ] = [];
                return $this->quotes;
            }

        }

        /**
         * Get service codes for matching origin
         *
         * @param $code
         *
         * @return string[]
         */
        private function getUPSServiceCode( $code ) {

            $eu = [
                'AT',
                'BE',
                'BG',
                'CY',
                'CZ',
                'DE',
                'DK',
                'EE',
                'ES',
                'FI',
                'FR',
                'GR',
                'HR',
                'HU',
                'IE',
                'IT',
                'LT',
                'LU',
                'LV',
                'MT',
                'NL',
                'PO',
                'PT',
                'RO',
                'SE',
                'SI',
                'SK',
            ];

            $codes = [
                '01' => [ 'name' => 'UPS Express', ],
                '02' => [
                    'name' => 'UPS Expedited',
                    'days' => '1-3 ' . MODULE_SHIPPING_ZIP_UPS_OATH_LANG_BUSINESS . ' ' . MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DAYS,
                ],
                '03' => [ 'name' => 'UPS Ground', ],
                '07' => [ 'name' => 'UPS Worldwide Express', ],
                '08' => [ 'name' => 'UPS Worldwide Expedited' ],
                '11' => [
                    'name' => 'UPS Standard',
                    'days' => '1-5 ' . MODULE_SHIPPING_ZIP_UPS_OATH_LANG_BUSINESS . ' ' . MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DAYS,
                ],
                '12' => [ 'name' => 'UPS 3 Day Select', ],
                '13' => [ 'name' => 'UPS Express Saver', ],
                '14' => [ 'name' => 'UPS Express Early', ],
                '17' => [ 'name' => 'UPS Worldwide Economy DDU', ],
                '54' => [ 'name' => 'UPS Worldwide Express Plus', ],
                '65' => [ 'name' => 'UPS Express Saver', ],
                '70' => [ 'name' => 'UPS Access Point Economy', ],
                '71' => [ 'name' => 'UPS Worldwide Express Freight Midday', ],
                '72' => [ 'name' => 'UPS Worldwide Economy DDP', ],
                '96' => [ 'name' => 'UPS Worldwide Express Freight', ],
            ];

            if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == 'CA' ) {
                //use base codes for Canada
            } else if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == 'US' ) {
                $codes[ '01' ] = [ 'name' => 'UPS Next Day Air', ];
                $codes[ '02' ] = [ 'name' => 'UPS 2nd Day Air', ];
                $codes[ '13' ] = [ 'name' => 'UPS Next Day Air Saver', ];
                $codes[ '14' ] = [ 'name' => 'UPS Next Day Air Early', ];
                $codes[ '59' ] = [ 'name' => 'UPS 2nd Day Air A.M.', ];
                $codes[ '65' ] = [ 'name' => 'UPS Worldwide Saver', ];
            } else if ( in_array( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ], $eu ) ) {
                $codes[ '07' ] = [ 'name' => 'UPS Express', ];
                $codes[ '08' ] = [ 'name' => 'UPS Expedited', ];
                $codes[ '70' ] = [ 'name' => 'UPS Access Point Economy', ];
            } else if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == 'MX' ) {
                $codes[ '70' ] = [ 'name' => 'UPS Access Point Economy', ];
                $codes[ '07' ] = [ 'name' => 'UPS Express', ];
                $codes[ '08' ] = [ 'name' => 'UPS Expedited', ];
            } else if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == 'PL' ) {
                $codes[ '70' ] = [ 'name' => 'UPS Access Point Economy', ];
                $codes[ '83' ] = [ 'name' => 'UPS Today Dedicated Courrier', ];
                $codes[ '85' ] = [ 'name' => 'UPS Today Express', ];
                $codes[ '82' ] = [ 'name' => 'UPS Today Standard', ];
                $codes[ '86' ] = [ 'name' => 'UPS Today Express Saver', ];
                $codes[ '07' ] = [ 'name' => 'UPS Express', ];
                $codes[ '08' ] = [ 'name' => 'UPS Expedited', ];
                $codes[ '54' ] = [ 'name' => 'UPS Express Plus', ];
            } else if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == 'PR' ) {
                $codes[ '02' ] = [ 'name' => 'UPS 2nd Day Air', ];
                $codes[ '01' ] = [ 'name' => 'UPS Next Day Air', ];
                $codes[ '14' ] = [ 'name' => 'UPS Next Day Air Early', ];
                $codes[ '65' ] = [ 'name' => 'UPS Worldwide Saver', ];
            } else if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == 'DE' ) {
                $codes[ '74' ] = [ 'name' => 'UPS Express 12:00', ];
            } else {
                $codes[ '07' ] = [ 'name' => 'UPS Express', ];
                $codes[ '65' ] = [ 'name' => 'UPS Worldwide Saver', ];
            }

            return $codes[ $code ];
        }

        private function getUPSoAuthQuote() {

            global $shipping_num_boxes, $language;

            $packaging = [];

            for ( $i = 0; $i < $shipping_num_boxes; $i++ ) {

                if ( !empty( $this->shipment_details[ 'shipment' ][ 'insure_shipment' ] ) ) {

                    $item_value                   = ceil( $this->shipment_details[ 'shipment' ][ 'total_value' ] / $shipping_num_boxes);

                    $insurance = [
                        'DeclaredValue' => [
                            'CurrencyCode'  => $this->shipment_details[ 'shipment' ][ 'currency' ],
                            'MonetaryValue' => "{$item_value}",
                        ],
                    ];

                    //only allow signature if in same country
                    if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == $this->shipment_details[ 'destination' ][ 'Address' ][ 'CountryCode' ] ) {
                        $insurance[ 'DeliveryConfirmation' ] = [
                            'DCISType' => '2',//signature required
                        ];
                    }

                } else {
                    $insurance = [];
                }

                if ( $this->shipment_details[ 'origin' ][ 'Address' ][ 'CountryCode' ] == $this->shipment_details[ 'destination' ][ 'Address' ][ 'CountryCode' ] ) {

                    $signature = !defined( 'MODULE_SHIPPING_ZIP_UPS_OATH_REQUIRE_SIGNATURE_TYPE' ) ? 'Signature Required' : MODULE_SHIPPING_ZIP_UPS_OATH_REQUIRE_SIGNATURE_TYPE;

                    $signature_value = !defined( 'MODULE_SHIPPING_ZIP_UPS_OATH_REQUIRE_SIGNATURE_VALUE' ) ? 0 : MODULE_SHIPPING_ZIP_UPS_OATH_REQUIRE_SIGNATURE_VALUE;

                    if ( (int)$this->shipment_details[ 'shipment' ][ 'total_value' ] >= (int)$signature_value ) {
                        if ( $signature == 'Signature Required' ) {
                            $insurance[ 'DeliveryConfirmation' ] = [
                                'DCISType' => '2',//signature required
                            ];
                        } else if ( $signature == 'Adult Signature Required' ) {
                            $insurance[ 'DeliveryConfirmation' ] = [
                                'DCISType' => '3',//signature required
                            ];
                        }
                    }
                }

                $weight = ceil( $this->shipment_details[ 'shipment' ] [ 'weight' ] / $shipping_num_boxes );

                $packaging[ $i ] = [
                    'PackagingType'         => [
                        'Code'        => '02',//Package
                        'Description' => 'Package',
                    ],
                    'Dimensions'            => [
                        'Length'            => "5",
                        'Width'             => "5",
                        'Height'            => "5",
                        'UnitOfMeasurement' => [
                            'Code'        => ( MODULE_SHIPPING_ZIP_UPS_OATH_WEIGHT_UNITS == 'LBS' ) ? 'IN' : 'CM',
                            'Description' => ( MODULE_SHIPPING_ZIP_UPS_OATH_WEIGHT_UNITS == 'LBS' ) ? 'Inches' : 'Centimeters',
                        ],
                    ],
                    'PackageWeight'         => [
                        'Weight'            => "$weight",
                        'UnitOfMeasurement' => [
                            'Code'        => MODULE_SHIPPING_ZIP_UPS_OATH_WEIGHT_UNITS,
                            'Description' => ( MODULE_SHIPPING_ZIP_UPS_OATH_WEIGHT_UNITS == 'LBS' ) ? 'Pounds' : 'Kilograms',
                        ],
                    ],
                    'PackageServiceOptions' => $insurance,

                ];

            }

            if ( !empty( MODULE_SHIPPING_ZIP_UPS_OATH_EXTRA_INSURANCE ) ) {
                if ( !empty( $this->shipment_details[ 'shipment' ][ 'insure_shipment' ] ) ) {

                    $extra_insurance_array = explode( ';', MODULE_SHIPPING_ZIP_UPS_OATH_EXTRA_INSURANCE );
                    $extra_insurance_rates = [];
                    $extra_insurance       = 0;
                    foreach ( $extra_insurance_array as $item ) {
                        if ( !empty( $item ) ) {
                            $this_rate = explode( ':', $item );
                            if ( count( $this_rate ) == 3 && empty( $extra_insurance_rates ) ) {
                                $extra_insurance_rates[] = $this_rate;
                            }
                        }
                    }

                    if ( !empty( $extra_insurance_rates ) ) {
                        $value = $this->shipment_details[ 'shipment' ][ 'total_value' ];
                        if ( $value >= $extra_insurance_rates[ 0 ][ 0 ] ) {
                            $rounded_total                  = ceil( $value / $extra_insurance_rates[ 0 ][ 2 ] ) * $extra_insurance_rates[ 0 ][ 2 ];
                            $extra_insurance                = ceil( $value / $extra_insurance_rates[ 0 ][ 2 ] ) * $extra_insurance_rates[ 0 ][ 1 ];
                            $this->shipment_extra_insurance = $extra_insurance;

                        }
                    }

                }
            }

            $shipment = [
                "RateRequest" => [
                    "Shipment" => [
                        "Shipper"                 => $this->shipment_details[ 'origin' ],
                        "ShipTo"                  => $this->shipment_details[ 'destination' ],
                        "Service"                 => [
                            "Code"        => "11",//Ignored when using Shop type in URL
                            "Description" => "UPS Standard"
                        ],
                        "NumOfPieces"             => count( $packaging ),
                        "Package"                 => $packaging,
                        'ShipmentServiceOptions'  => '',
                        'LargePackageIndicator'   => '',
                        'InvoiceLineTotal'        => [
                            'CurrencyCode'  => "{$this->shipment_details[ 'shipment' ][ 'currency' ]}",
                            'MonetaryValue' => "{$this->shipment_details[ 'shipment' ][ 'total_value' ]}",
                        ],
                        'TaxInformationIndicator' => '',
                    ]
                ]
            ];

            $headers = [
                "Authorization: Bearer {$this->api_connection [ 'api_token' ]}",
                "Content-Type: application/json",
                "transId: string",
            ];

//            var_dump( $shipment );

            try {

                $response = $this->curlRequest( $this->api_connection[ 'rate_host' ], 'POST', $headers, $shipment, true );

                if ( !empty( $response ) && !empty( $response[ 'RateResponse' ] ) && $response[ 'RateResponse' ][ 'Response' ][ 'ResponseStatus' ][ 'Code' ] == '1' ) {

                    $this->quotes = [
                        'id'     => $this->code,
                        'module' => MODULE_SHIPPING_ZIP_UPS_OATH_LANG_TEXT_TITLE,
                    ];

                    $methods = [];
                    $dow     = date( 'N' );

                    foreach ( $response[ 'RateResponse' ][ 'RatedShipment' ] as $estimate ) {

                        $service = $this->getUPSServiceCode( $estimate[ 'Service' ][ 'Code' ] );

                        if ( !empty( $service ) ) {

                            $load_rate = 1;
                            if ( !empty( MODULE_SHIPPING_ZIP_UPS_OATH_EXCLUDE ) ) {

                                $excludes = explode( ',', strtolower( MODULE_SHIPPING_ZIP_UPS_OATH_EXCLUDE ) );

                                foreach ( $excludes as $exclude ) {
                                    if ( strpos( strtolower( $service[ 'name' ] ), $exclude ) !== false ) {
                                        $load_rate = 0;
                                    }
                                }

                            }

                            if ( !empty( $load_rate ) ) {

                                if ( MODULE_SHIPPING_ZIP_UPS_OATH_HANDLING_TYPE == 'Flat Fee' ) {
                                    $estimate[ 'TotalCharges' ][ 'MonetaryValue' ] += MODULE_SHIPPING_ZIP_UPS_OATH_HANDLING;
                                } else if ( MODULE_SHIPPING_ZIP_UPS_OATH_HANDLING_TYPE == 'Percentage' ) {
                                    $estimate[ 'TotalCharges' ][ 'MonetaryValue' ] = ( ( MODULE_SHIPPING_ZIP_UPS_OATH_HANDLING * $estimate[ 'TotalCharges' ][ 'MonetaryValue' ] ) / 100 ) + $estimate[ 'TotalCharges' ][ 'MonetaryValue' ];
                                }

                                $number_of_days = ( empty( $estimate[ 'GuaranteedDelivery' ] ) ) ? 0 : $estimate[ 'GuaranteedDelivery' ][ 'BusinessDaysInTransit' ];

                                $number_of_days += empty( $number_of_days ) ? 0 : (int)MODULE_SHIPPING_ZIP_UPS_OATH_TURNAROUNDTIME;

                                if ( !empty( $number_of_days ) ) {
                                    $number_of_days = ( ( 5 - $dow ) <= 0 ) ? $number_of_days + 2 : $number_of_days;
                                    $arrive_date    = date_create();
                                    date_add( $arrive_date, date_interval_create_from_date_string( $number_of_days . ' days' ) );
                                    $arrive_date_formatted = date_format( $arrive_date, 'Y-m-d' );
                                    $service[ 'name' ]     .= ' (' . $arrive_date_formatted;

                                    if ( !empty( $estimate[ 'GuaranteedDelivery' ] ) ) {
                                        if ( !empty( $estimate[ 'GuaranteedDelivery' ][ 'DeliveryByTime' ] ) ) {
                                            $service[ 'name' ] .= ' - ' . $estimate[ 'GuaranteedDelivery' ][ 'DeliveryByTime' ];
                                        }
                                    }

                                    $service[ 'name' ] .= ')';

                                } else if ( !empty( $service[ 'days' ] ) ) {
                                    $service[ 'name' ] .= ' (' . $service[ 'days' ] . ')';
                                }

                                $days = ( $number_of_days == 1 ) ? MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DAY : MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DAYS;

                                $daystext = empty( $number_of_days ) ? '' : '<br/>' . $number_of_days . ' ' . MODULE_SHIPPING_ZIP_UPS_OATH_LANG_BUSINESS . ' ' . $days . ' ' . MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DELIVERY;

                                $methods[] = [
                                    'id'    => $estimate[ 'Service' ][ 'Code' ],
                                    'title' => $service[ 'name' ] . '<br/>' . $shipping_num_boxes . ' ' . MODULE_SHIPPING_ZIP_UPS_OATH_LANG_PACKAGES . ' @ ' . $this->shipment_details[ 'shipment' ] [ 'weight' ] . ' ' . MODULE_SHIPPING_ZIP_UPS_OATH_WEIGHT_UNITS . $daystext,
                                    'cost'  => $estimate[ 'TotalCharges' ][ 'MonetaryValue' ] + $this->shipment_extra_insurance,
                                ];
                            }

                        }
                    }

                    $this->sortRates( $methods, 'cost' );

                    $this->quotes[ 'methods' ] = $methods;

                    $this->quotes[ 'icon' ] = $this->image( 'images/icons/shipping_UPS.png' );

                } else {
                    throw new Exception( 'Errors in Resonse' );
                }
            } catch ( Exception $ex ) {

                if ( MODULE_SHIPPING_ZIP_UPS_OATH_SCREEN_ERRORS == 'Yes' ) {
                    $methods = [];
                    if ( !empty( $response ) ) {
                        $this->debugToScreen( $response );
                    }
                    if ( !empty( $ex ) ) {
                        $this->debugToScreen( $ex );
                    }
                }

                if ( MODULE_SHIPPING_ZIP_UPS_OATH_EMAIL_ERRORS == 'Yes' ) {

                    $json  = ( !empty( $response ) ) ? json_encode( $response ) : '';
                    $json2 = json_encode( $ex );

                    error_log( "Error from ups. experienced by customer with id " . $_SESSION[ 'customer_id' ] . " on " . date( 'Y-m-d H:i:s' ) . ' ' . $json . ' ' . $json2, 1, STORE_OWNER_EMAIL_ADDRESS );
                }

            }

        }

        /**
         * Next function used for sorting the shipping quotes on rate: low to high is default.
         *
         * @param     $arr
         * @param     $col
         * @param int $dir
         *
         * @return void
         */
        function sortRates( &$arr, $col, $dir = SORT_ASC ) {

            $sort_col = [];
            foreach ( $arr as $key => $row ) {
                $sort_col[ $key ] = $row[ $col ];
            }

            array_multisort( $sort_col, $dir, $arr );
        }

        /**
         * Calculate shipment weight based on order lines
         */
        private function calcWeight() {

            global $order, $shipping_weight, $shipping_num_boxes;

            //use class weights from shipping class
            $weight = $shipping_weight * $shipping_num_boxes;

            $this->shipment_details[ 'shipment' ] [ 'weight' ] = ( empty( $weight ) ) ? 0.1 : $weight;

        }

        /**
         * Set destination of shipment
         */
        private function setDestination() {

            global $order;

            $state_prov = '';
            $query      = $this->db_query( "select zone_code from zones where zone_name = '" . $this->db_input( $order->delivery[ 'state' ] ) . "' and zone_country_id = '" . $order->delivery[ 'country' ][ 'id' ] . "'" );
            $zone       = $this->fetch_array( $query );
            if ( !empty( $zone ) ) {
                $state_prov = $zone[ 'zone_code' ];
            }

            $name = '';
            $name .= ( !empty( $order->delivery[ 'entry_lastname' ] ) ) ? $order->delivery[ 'entry_lastname' ] : '';
            $name .= ( !empty( $name ) ) ? ', ' : '';
            $name .= ( !empty( $order->delivery[ 'entry_firstname' ] ) ) ? $order->delivery[ 'entry_firstname' ] : '';

            if ( $order->delivery[ 'country' ][ 'iso_code_2' ] == 'US' ) {
                $postal = substr( str_replace( ' ', '', $order->delivery[ 'postcode' ] ), 0, 5 );
            } else if ( $order->delivery[ 'country' ][ 'iso_code_2' ] == 'BR' ) {
                $postal = substr( str_replace( ' ', '', $order->delivery[ 'postcode' ] ), 0, 5 );
            } else if ( $order->delivery[ 'country' ][ 'iso_code_2' ] == 'CA' ) {
                $postal = strtoupper( str_replace( ' ', '', $order->delivery[ 'postcode' ] ) );
            } else {
                $postal = $order->delivery[ 'postcode' ];
            }

            $this->shipment_details[ 'destination' ] = [
                "Name"    => $name,
                "Address" => [
                    "AddressLine"       => [ $order->delivery[ 'street_address' ]
                    ],
                    "City"              => $order->delivery[ 'city' ],
                    "StateProvinceCode" => $state_prov,
                    "PostalCode"        => $postal,
                    "CountryCode"       => $order->delivery[ 'country' ][ 'iso_code_2' ]
                ]
            ];

        }

        /**
         * Set origin of shipment
         */
        private function setOrigin() {

            $store_address = explode( "\n", STORE_ADDRESS );

            if ( MODULE_SHIPPING_ZIP_UPS_OATH_COUNTRY == 'US' ) {
                $postal = substr( str_replace( ' ', '', MODULE_SHIPPING_ZIP_UPS_OATH_POSTALCODE ), 0, 5 );
            } else if ( MODULE_SHIPPING_ZIP_UPS_OATH_COUNTRY == 'CA' ) {
                $postal = substr( str_replace( ' ', '', MODULE_SHIPPING_ZIP_UPS_OATH_POSTALCODE ), 0, 6 );
                $postal = substr( $postal, 0, 3 ) . ' ' . substr( $postal,  -3 );
            } else {
                $postal = strtoupper( str_replace( ' ', '', MODULE_SHIPPING_ZIP_UPS_OATH_POSTALCODE ) );
            }

            $this->shipment_details[ 'origin' ] = [
                "Name"          => STORE_OWNER,
                "ShipperNumber" => MODULE_SHIPPING_ZIP_UPS_OATH_USER_NUMBER,
                "Address"       => [
                    "AddressLine"       => $store_address,
                    "City"              => MODULE_SHIPPING_ZIP_UPS_OATH_CITY,
                    "StateProvinceCode" => MODULE_SHIPPING_ZIP_UPS_OATH_STATEPROV,
                    "PostalCode"        => $postal,
                    "CountryCode"       => MODULE_SHIPPING_ZIP_UPS_OATH_COUNTRY
                ]
            ];

            $this->shipment_details[ 'ship_from' ] = [
                "Name"    => STORE_OWNER,
                "Address" => [
                    "AddressLine"       => $store_address,
                    "City"              => MODULE_SHIPPING_ZIP_UPS_OATH_CITY,
                    "StateProvinceCode" => MODULE_SHIPPING_ZIP_UPS_OATH_STATEPROV,
                    "PostalCode"        => $postal,
                    "CountryCode"       => MODULE_SHIPPING_ZIP_UPS_OATH_COUNTRY
                ]
            ];

        }

        /**
         * @param     $arraytoshow
         * @param int $showfullscreen
         */
        public function ShowMe( $arraytoshow, $showfullscreen = 0 ) {

            if ( $showfullscreen == 1 ) {
                echo '<div style="position: fixed; top: 0px; left: 0px; width: 2000px; height: 1000px; overflow: auto;">';
            }
            echo '<pre>';
            var_dump( $arraytoshow );
            echo '</pre>';
            if ( $showfullscreen == 1 ) {
                echo '</div>';
            }
        }

        /**
         * @param $xmlRequest
         */
        public function debugToScreen( $xmlRequest ) {

            $this->ShowMe( $xmlRequest );

        }

        /**
         * @return string[][]
         */
        protected function get_parameters() {

            if ( version_compare( '1.0.8.3', $this->phoenix_version() ) >= 0 ) {
                $select_set_func                  = 'tep_cfg_select_option';
                $select_geo_set_func              = 'tep_cfg_pull_down_zone_classes';
                $select_geo_zone_use_func         = 'tep_get_geo_zone_name';
                $select_tax_class_title           = 'tep_get_tax_class_title';
                $select_cfg_pull_down_tax_classes = 'tep_cfg_pull_down_tax_classes';

            } else {
                $select_set_func                  = 'Config::select_one';
                $select_geo_set_func              = 'Config::select_geo_zone';
                $select_geo_zone_use_func         = 'geo_zone::fetch_name';
                $select_tax_class_title           = 'Tax::get_class_title';
                $select_cfg_pull_down_tax_classes = 'Config::select_tax_class';

            }

            return [
                $this->config_key_base . 'STATUS'      => [
                    'title'    => 'Enable UPS Shipping',
                    'value'    => 'True',
                    'desc'     => 'Do you want to offer UPS shipping?',
                    'set_func' => "$select_set_func(['True', 'False'], ",
                ],
                $this->config_key_base . 'USER_NUMBER' => [
                    'title' => 'UPS Account Number',
                    'value' => '',
                    'desc'  => 'Enter the Your UPS Account Number.',
                ],
                $this->config_key_base . 'USER_ID'     => [
                    'title' => 'UPS API Client ID',
                    'value' => '',
                    'desc'  => 'Enter the Your UPS API Client ID.',
                ],

                $this->config_key_base . 'ACCESS_KEY'              => [
                    'title' => 'UPS API Client Secret',
                    'value' => '',
                    'desc'  => 'Enter your UPS API Client Secret',
                ],
                $this->config_key_base . 'INSURE'                  => [
                    'title'    => 'Enable Insurance',
                    'value'    => 'True',
                    'desc'     => 'Do you want to insure packages shipped by UPS?',
                    'set_func' => "$select_set_func(['True', 'False'], ",
                ],
                $this->config_key_base . 'EXTRA_INSURANCE'         => [
                    'title' => 'Extra Insurance',
                    'value' => '',
                    'desc'  => 'With the removal of UPS Capital Insurance from the main API, if your account does not calculate insurance rates you can use this field as follows. Minimum:Amount:Per; for example: 100:0.96:100; would charge $0.96 per $100 once a minimum value reaches $100.',
                ],
                $this->config_key_base . 'REQUIRE_SIGNATURE_TYPE'  => [
                    'title'    => 'Require Signature',
                    'value'    => 'Signature Required',
                    'desc'     => 'Do you want to include a signature requirement? If enabled, this will increase the cost of shipping. (Only applies to shipments within same country Canada/USA)',
                    'set_func' => "$select_set_func(['None', 'Signature Required', 'Adult Signature Required'], ",
                ],
                $this->config_key_base . 'REQUIRE_SIGNATURE_VALUE' => [
                    'title' => 'At what value does the shipment require a signature as specified above? Enter an integer value like 300 or 0. Any shipment value above this amount going within the same country will require a signature and amounts will be adjusted.',
                    'value' => '0',
                    'desc'  => 'Enter a shipment value for signature requirement.',
                ],
                $this->config_key_base . 'CITY'                    => [
                    'title' => 'Origin City',
                    'value' => '',
                    'desc'  => 'Enter the name of the origin city.',
                ],
                $this->config_key_base . 'STATEPROV'               => [
                    'title' => 'Origin State/Province',
                    'value' => '',
                    'desc'  => 'Enter the two-letter code for your origin state/province.',
                ],
                $this->config_key_base . 'COUNTRY'                 => [
                    'title' => 'Origin Country',
                    'value' => '',
                    'desc'  => 'Enter the two-letter code for your origin country.',
                ],
                $this->config_key_base . 'POSTALCODE'              => [
                    'title' => 'Origin Zip/Postal Code',
                    'value' => '',
                    'desc'  => 'Enter your origin zip/postalcode (from which the parcel will be sent).',
                ],
                $this->config_key_base . 'MODE'                    => [
                    'title'    => 'Test or Production Mode',
                    'value'    => 'Test',
                    'desc'     => 'Use this module in Test  or Production mode?',
                    'set_func' => "$select_set_func(['Test', 'Production'], ",
                ],
                $this->config_key_base . 'EXCLUDE'                 => [
                    'title' => 'Exclude delivery methods containing:',
                    'value' => '',
                    'desc'  => 'Example: Ground,Express',
                ],
                $this->config_key_base . 'WEIGHT_UNITS'            => [
                    'title'    => 'Weight Units',
                    'value'    => 'KGS',
                    'desc'     => 'Unit of weight for this shipping method.',
                    'set_func' => "$select_set_func(['KGS', 'LBS'], ",
                ],
                $this->config_key_base . 'HANDLING_TYPE'           => [
                    'title'    => 'Handling Type',
                    'value'    => 'Flat Fee',
                    'desc'     => 'Handling type for this shipping method.',
                    'set_func' => "$select_set_func(['Flat Fee', 'Percentage'], ",
                ],
                $this->config_key_base . 'HANDLING'                => [
                    'title' => 'Handling Fee',
                    'value' => '0',
                    'desc'  => 'Handling fee for this shipping method.',
                ],
                $this->config_key_base . 'TAX_CLASS'               => [
                    'title'    => 'Tax Class',
                    'value'    => '0',
                    'desc'     => 'Use the following tax class on the shipping fee.',
                    'use_func' => "$select_tax_class_title",
                    'set_func' => "$select_cfg_pull_down_tax_classes(",
                ],
                $this->config_key_base . 'ZONE'                    => [
                    'title'    => 'Shipping Zone',
                    'value'    => '0',
                    'desc'     => 'If a zone is selected, only enable this shipping method for that zone.',
                    'use_func' => "$select_geo_zone_use_func ",
                    'set_func' => "$select_geo_set_func(",
                ],
                $this->config_key_base . 'EMAIL_ERRORS'            => [
                    'title'    => 'Email UPS errors',
                    'value'    => 'Yes',
                    'desc'     => 'Do you want to receive UPS errors by email?',
                    'set_func' => "$select_set_func(['Yes', 'No'], ",
                ],
                $this->config_key_base . 'SCREEN_ERRORS'           => [
                    'title'    => 'Show UPS errors (<span class="text-danger">make sure to turn this off once done testing</span>)',
                    'value'    => 'Yes',
                    'desc'     => 'Do you want to show UPS errors on screen?',
                    'set_func' => "$select_set_func(['Yes', 'No'], ",
                ],
                $this->config_key_base . 'TURNAROUNDTIME'          => [
                    'title' => 'Enter Turn Around Time',
                    'value' => '1',
                    'desc'  => 'Turn Around Time (days).',
                ],
                $this->config_key_base . 'SORT_ORDER'              => [
                    'title' => 'Sort Order of display',
                    'value' => '30',
                    'desc'  => 'Sort order of display. Lowest is displayed first.',
                ],
            ];
        }

    }
    