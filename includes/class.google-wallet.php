<?php
/*
 * Copyright 2022 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require plugin_dir_path( __DIR__ ) . 'vendor/autoload.php';

// [START setup]
// [START imports]
use Firebase\JWT\JWT;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Client as GoogleClient;
use Google\Service\Walletobjects;
use Google\Service\Walletobjects\GenericObject;
use Google\Service\Walletobjects\GenericClass;
use Google\Service\Walletobjects\Barcode;
use Google\Service\Walletobjects\ImageModuleData;
use Google\Service\Walletobjects\LinksModuleData;
use Google\Service\Walletobjects\TextModuleData;
use Google\Service\Walletobjects\TranslatedString;
use Google\Service\Walletobjects\LocalizedString;
use Google\Service\Walletobjects\ImageUri;
use Google\Service\Walletobjects\Image;
use Google\Service\Walletobjects\Uri;
// [END imports]

/** Demo class for creating and managing Generic passes in Google Wallet. */
class PMPro_Google_Wallet_Generic {
	/**
	 * The Google API Client
	 * https://github.com/google/google-api-php-client
	 */
	public GoogleClient $client;

	/**
	 * Path to service account key file from Google Cloud Console. Environment
	 * variable: GOOGLE_APPLICATION_CREDENTIALS.
	 */
	public string $keyFilePath;

	/**
	 * Service account credentials for Google Wallet APIs.
	 */
	public ServiceAccountCredentials $credentials;

	/**
	 * Google Wallet service client.
	 */
	public Walletobjects $service;

	public function __construct() {
		$this->keyFilePath = getenv( 'GOOGLE_APPLICATION_CREDENTIALS' ) ?: plugin_dir_path( __FILE__ ) . 'test-license.json'; // Tweak this once we have our license file as well.

		$this->auth();
	}

	/**
	 * Create authenticated HTTP client using a service account file.
	 */
	public function auth() {
		$this->credentials = new ServiceAccountCredentials(
			Walletobjects::WALLET_OBJECT_ISSUER,
			$this->keyFilePath
		);

		// Initialize Google Wallet API service
		$this->client = new GoogleClient();
		$this->client->setApplicationName( 'PMPRO_WALLET_CARD' );
		$this->client->setScopes( Walletobjects::WALLET_OBJECT_ISSUER );
		$this->client->setAuthConfig( $this->keyFilePath );

		$this->service = new Walletobjects( $this->client );
	}

	/**
	 * Create a class.
	 *
	 * @param string $issuerId The issuer ID being used for this request.
	 * @param string $classSuffix Developer-defined unique ID for this pass class.
	 *
	 * @return string The pass class ID: "{$issuerId}.{$classSuffix}"
	 */
	public function createClass( string $issuerId, string $classSuffix ) {
		// Check if the class exists
		try {
			$this->service->genericclass->get( "{$issuerId}.{$classSuffix}" );
			return false;
		} catch ( Google\Service\Exception $ex ) {
			if ( empty( $ex->getErrors() ) || $ex->getErrors()[0]['reason'] != 'classNotFound' ) {
				return false;
			}
		}

		// See link below for more information on required properties
		// https://developers.google.com/wallet/generic/rest/v1/genericclass
		$newClass = new GenericClass(
			array(
				'id' => "{$issuerId}.{$classSuffix}",
			)
		);

		$response = $this->service->genericclass->insert( $newClass );

		return $response->id;
	}

	/**
	 * Create an object.
	 *
	 * @param string $issuerId The issuer ID being used for this request.
	 * @param string $classSuffix Developer-defined unique ID for this pass class.
	 * @param string $objectSuffix Developer-defined unique ID for this pass object.
	 *
	 * @return string The pass object ID: "{$issuerId}.{$objectSuffix}"
	 */
	public function createObject( string $issuerId, string $classSuffix, string $objectSuffix, $user = null ) {

		// Check if the object exists
		try {
			$this->service->genericobject->get( "{$issuerId}.{$objectSuffix}" );
			return false;
		} catch ( Google\Service\Exception $ex ) {
			if ( empty( $ex->getErrors() ) || $ex->getErrors()[0]['reason'] != 'resourceNotFound' ) {
				return false;
			}
		}

		// See link below for more information on required properties
		// https://developers.google.com/wallet/generic/rest/v1/genericobject
		$newObject = new GenericObject( $this->wallet_information( $issuerId, $classSuffix, $objectSuffix, $user ) );

		$response = $this->service->genericobject->insert( $newObject );

		return $response->id;
	}

	/**
	 * Update an object.
	 *
	 * **Warning:** This replaces all existing object attributes!
	 *
	 * @param string $issuerId The issuer ID being used for this request.
	 * @param string $objectSuffix Developer-defined unique ID for this pass object.
	 *
	 * @return string The pass object ID: "{$issuerId}.{$objectSuffix}"
	 */
	public function updateObject( string $issuerId, string $classSuffix, string $objectSuffix, $user = null ) {
		// Check if the object exists
		try {
			$updatedObject = $this->service->genericobject->get( "{$issuerId}.{$objectSuffix}" );
		} catch ( Google\Service\Exception $ex ) {
			return false; // Die quietly for now.
		}

		// Try to update it with the information from the wallet_information function.
		$updatedObject = new GenericObject( $this->wallet_information( $issuerId, $classSuffix, $objectSuffix, $user ) );

		$response = $this->service->genericobject->update( "{$issuerId}.{$objectSuffix}", $updatedObject );

		return $response->id;
	}

	/**
	 * Generate a signed JWT that creates a new pass class and object.
	 *
	 * When the user opens the "Add to Google Wallet" URL and saves the pass to
	 * their wallet, the pass class and object defined in the JWT are
	 * created. This allows you to create multiple pass classes and objects in
	 * one API call when the user saves the pass to their wallet.
	 *
	 * @param string $issuerId The issuer ID being used for this request.
	 * @param string $classSuffix Developer-defined unique ID for the pass class.
	 * @param string $objectSuffix Developer-defined unique ID for the pass object.
	 *
	 * @return string An "Add to Google Wallet" link.
	 */
	public function createJwtNewObjects( string $issuerId, string $classSuffix, string $objectSuffix ) {
		// See link below for more information on required properties
		// https://developers.google.com/wallet/generic/rest/v1/genericclass
		$newClass = new GenericClass(
			array(
				'id' => "{$issuerId}.{$classSuffix}",
			)
		);

		// See link below for more information on required properties
		// https://developers.google.com/wallet/generic/rest/v1/genericobject
		$newObject = new GenericObject( $this->wallet_information( $issuerId, $classSuffix, $objectSuffix ) );

		// The service account credentials are used to sign the JWT
		$serviceAccount = json_decode( file_get_contents( $this->keyFilePath ), true );

		// Create the JWT as an array of key/value pairs
		$claims = array(
			'iss'     => $serviceAccount['client_email'],
			'aud'     => 'google',
			'origins' => array( 'www.example.com' ),
			'typ'     => 'savetowallet',
			'payload' => array(
				'genericClasses' => array(
					$newClass,
				),
				'genericObjects' => array(
					$newObject,
				),
			),
		);

		$token = JWT::encode(
			$claims,
			$serviceAccount['private_key'],
			'RS256'
		);

		return "https://pay.google.com/gp/v/save/{$token}";
	}

	// Make it easier to filter out wallet information YO! //// Tweak this and add doc blocks.
	public function wallet_information( $issuerId, $classSuffix, $objectSuffix, $user = null ) {
		global $current_user;
		// if no user is passed, let's use the current user.
		if ( empty( $user->ID ) ) {
			$user = $current_user;
		}

		/**
		 * Arguments used for Apple Wallet Pass.
		 *
		 * @param array $args The arguments for the Apple Wallet Pass.
		 * @param user $user The WordPress user.
		 */
		$args = apply_filters(
			'pmpro_membership_card_google_wallet_args',
			array(
				'site_name'          => get_bloginfo( 'name' ),
				'site_url'           => get_bloginfo( 'url' ),
				'serial_number'      => $user->ID,
				'logo'               => 'https://www.paidmembershipspro.com/images/2022/Paid-Memberships-Pro_icon_72DPI.png',
				'member_name'        => pmpro_membership_card_return_user_name( $user ),
				'background_color'   => '#2697C8',
				'membership_name'    => $user->membership_level->name,
				'membership_since'   => date_i18n( get_option( 'date_format' ), pmpro_getMemberStartDate( $user->ID ) ),
				'membership_enddate' => pmpro_membership_card_return_end_date( $user ),
                'account_page'       => pmpro_url( 'account' ),
				'barcode_message'    => sprintf( __( '%1$s - %2$s - expires %3$s', 'pmpro-membership-card' ), pmpro_membership_card_return_user_name( $user ), $user->membership_level->name, pmpro_membership_card_return_end_date( $user ) ),
			),
			$user
		);

		return array(
			'id'                 => "{$issuerId}.{$objectSuffix}",
			'classId'            => "{$issuerId}.{$classSuffix}",
			'state'              => 'ACTIVE',
            'header'             => new LocalizedString(
				array(
					'defaultValue' => new TranslatedString(
						array(
							'language' => 'en-US',
							'value'    => esc_html__( 'Membership Card', 'pmpro-membership-card' ),
						)
					),
				)
			),
			'subheader'          => new LocalizedString(
				array(
					'defaultValue' => new TranslatedString(
						array(
							'language' => 'en-US',
							'value'    => esc_html( $args['member_name'] )
						)
					),
				)
			),
			'textModulesData'    => array(
				new TextModuleData(
					array(
						'header' => esc_html__( 'Membership Level', 'pmpro-membership-card' ),
						'body'   => esc_html( $args['membership_name'] ),
						'id'     => 'pmpro_text_module_level_' . $user->membership_level->ID,
                    )
				),
                new TextModuleData(
                    array(
                        'header' => esc_html__( 'Membership Expires', 'pmpro-membership-card' ),
                        'body'   => esc_html( $args['membership_enddate'] ),
                        'id'     => 'pmpro_text_module_expires_' . $user->membership_level->ID,
                    )
                    ),
                    new TextModuleData(
                    array(
                        'header' => esc_html__( 'Status', 'pmpro-membership-card' ),
                        'body'   => esc_html__( 'Active', 'pmpro-membership-card' ),
                        'id'     => 'pmpro_text_module_status_' . $user->membership_level->ID,
                    )
                )
			),
			'linksModuleData'    => new LinksModuleData(
				array(
					'uris' => array(
						new Uri(
							array(
								'uri'         => esc_url( $args['account_page'] ),
								'description' => esc_html__( 'Account Page', 'pmpro-membership-card' ),
								'id'          => 'pmpro_site_account_url',
							)
						),
					),
				)
			),
			'barcode'            => new Barcode(
				array(
					'type'  => 'QR_CODE',
					'value' => esc_html( $args['barcode_message'] ),
				)
			),
			'cardTitle'          => new LocalizedString(
				array(
					'defaultValue' => new TranslatedString(
						array(
							'language' => 'en-US',
							'value'    => esc_html( $args['site_url'] ),
						)
					),
				)
			),
			'hexBackgroundColor' => $args['background_color'],
			'logo'               => new Image(
				array(
					'sourceUri'          => new ImageUri(
						array(
							'uri' => $args['logo'],
						)
					),
					'contentDescription' => new LocalizedString(
						array(
							'defaultValue' => new TranslatedString(
								array(
									'language' => 'en-US',
									'value'    => 'Membership Card',
								)
							),
						)
					),
				)
			),
		);
	}
}
