<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translates the Hebrew breed labels the CPP API's /info/breeds currently
 * returns into English for the registration dropdown.
 *
 * The API doc (v0.2, section 3.6) defines `label` as a plain string with no
 * language/locale parameter, so there is no way to ask the API for English.
 * Until the backend exposes one (worth asking them -- see the untranslated
 * log event below), this maps known Hebrew names to English. Only `label`
 * is rewritten; `id` is never touched, so /register still receives the
 * API's own breed_id.
 *
 * Anything not in the map is left exactly as the API sent it (so the
 * dropdown never loses a breed), and -- when transaction logging is on --
 * the leftover labels are written to the log (at most once a day) so the
 * map can be extended or the backend can be asked for English labels.
 *
 * Extend without editing this file via the `govetty_breed_translations`
 * filter: add_filter( 'govetty_breed_translations', fn( $map ) => $map + array( 'עברית' => 'English' ) );
 */
class Govetty_Breed_Translator {

	/**
	 * Hebrew => English. Keys are normalized (see normalize()) when the map
	 * is used, so niqqud / geresh variants of the same spelling still match.
	 */
	private static function map() {
		$map = array(
			// Dogs.
			'ביגל'                        => 'Beagle',
			'גולדן רטריבר'                => 'Golden Retriever',
			'לברדור'                      => 'Labrador Retriever',
			'לברדור רטריבר'               => 'Labrador Retriever',
			'פודל'                        => 'Poodle',
			'רועה גרמני'                  => 'German Shepherd',
			'רועה בלגי'                   => 'Belgian Shepherd',
			'רועה אוסטרלי'                => 'Australian Shepherd',
			'בורדר קולי'                  => 'Border Collie',
			'קולי'                        => 'Collie',
			'בולדוג'                      => 'Bulldog',
			'בולדוג צרפתי'                => 'French Bulldog',
			'בולדוג אנגלי'                => 'English Bulldog',
			'האסקי'                       => 'Siberian Husky',
			'האסקי סיבירי'                => 'Siberian Husky',
			'מלמוט אלסקני'                => 'Alaskan Malamute',
			'רוטוויילר'                   => 'Rottweiler',
			'דוברמן'                      => 'Doberman',
			'שיצו'                        => 'Shih Tzu',
			'יורקשייר טרייר'              => 'Yorkshire Terrier',
			"צ'יוואווה"                   => 'Chihuahua',
			'פומרניאן'                    => 'Pomeranian',
			'פקינז'                       => 'Pekingese',
			'מלטז'                        => 'Maltese',
			'בוקסר'                       => 'Boxer',
			'קוקר ספנייל'                 => 'Cocker Spaniel',
			'ספרינגר ספנייל'              => 'Springer Spaniel',
			"קבליר קינג צ'ארלס ספנייל"    => 'Cavalier King Charles Spaniel',
			'תחש'                         => 'Dachshund',
			'קורגי'                       => 'Corgi',
			'סנט ברנרד'                   => 'Saint Bernard',
			'ברנר סנהונד'                 => 'Bernese Mountain Dog',
			'דלמטי'                       => 'Dalmatian',
			'גרייהאונד'                   => 'Greyhound',
			'פיטבול'                      => 'Pit Bull',
			'סטפורדשייר טרייר'            => 'Staffordshire Terrier',
			'בולטרייר'                    => 'Bull Terrier',
			"ג'ק ראסל טרייר"              => 'Jack Russell Terrier',
			'וסטי'                        => 'West Highland White Terrier',
			'שנאוצר'                      => 'Schnauzer',
			'קאנה קורסו'                  => 'Cane Corso',
			'מסטיף'                       => 'Mastiff',
			'אקיטה'                       => 'Akita',
			'שיבא אינו'                   => 'Shiba Inu',
			'סמויד'                       => 'Samoyed',
			'ויימרנר'                     => 'Weimaraner',
			'פוינטר'                      => 'Pointer',
			'סטר'                         => 'Setter',
			'סטר אירי'                    => 'Irish Setter',
			'פאג'                         => 'Pug',
			'בישון פריזה'                 => 'Bichon Frise',
			'לאסה אפסו'                   => 'Lhasa Apso',
			'פפיון'                       => 'Papillon',
			'האוואנזה'                    => 'Havanese',
			'כלב כנעני'                   => 'Canaan Dog',
			'כנעני'                       => 'Canaan Dog',
			// Cats.
			'בריטי קצר שיער'              => 'British Shorthair',
			'חתול בריטי קצר שיער'         => 'British Shorthair',
			'בריטיש שורטהייר'             => 'British Shorthair',
			'חתול בית קצר שיער'           => 'Domestic Shorthair',
			'חתול בית ארוך שיער'          => 'Domestic Longhair',
			'חתול בית'                    => 'Domestic Cat',
			'חתול רחוב'                   => 'Domestic Cat',
			'סיאמי'                       => 'Siamese',
			'פרסי'                        => 'Persian',
			'מיין קון'                    => 'Maine Coon',
			'בנגל'                        => 'Bengal',
			'ראגדול'                      => 'Ragdoll',
			'סקוטיש פולד'                 => 'Scottish Fold',
			'ספינקס'                      => 'Sphynx',
			'אביסיני'                     => 'Abyssinian',
			'בירמני'                      => 'Birman',
			'בורמזי'                      => 'Burmese',
			'נורווגי'                     => 'Norwegian Forest Cat',
			'רוסי כחול'                   => 'Russian Blue',
			'אנגורה טורקי'                => 'Turkish Angora',
			'אקזוטי'                      => 'Exotic Shorthair',
			// Generic buckets that apply to both species.
			'מעורב'                       => 'Mixed breed',
			'חתול מעורב'                  => 'Mixed breed',
			'כלב מעורב'                   => 'Mixed breed',
			'לא ידוע'                     => 'Unknown',
			'אחר'                         => 'Other',
		);

		$map = apply_filters( 'govetty_breed_translations', $map );

		$normalized = array();
		foreach ( $map as $he => $en ) {
			$normalized[ self::normalize( $he ) ] = $en;
		}
		return $normalized;
	}

	/**
	 * Strips niqqud/cantillation, unifies the several apostrophe/quote
	 * characters Hebrew text uses (geresh, gershayim, curly quotes) and
	 * collapses whitespace, so "צ׳יוואווה" and "צ'יוואווה" are one key.
	 */
	private static function normalize( $label ) {
		$label = (string) $label;
		$label = preg_replace( '/[\x{0591}-\x{05C7}]/u', '', $label );
		$label = str_replace( array( "\u{05F3}", "\u{2019}", "\u{2018}", '`', "\u{05F4}" ), array( "'", "'", "'", "'", '"' ), $label );
		$label = preg_replace( '/\s+/u', ' ', $label );
		return trim( $label );
	}

	private static function has_hebrew( $label ) {
		return 1 === preg_match( '/[\x{0590}-\x{05FF}]/u', (string) $label );
	}

	/**
	 * @param array $body Decoded /info/breeds response body.
	 * @return array Same shape, English labels where known, re-sorted A-Z.
	 */
	public static function translate_body( $body ) {
		if ( ! is_array( $body ) ) {
			return $body;
		}

		$map         = self::map();
		$untranslated = array();

		foreach ( array( 'dog_breeds', 'cat_breeds' ) as $key ) {
			if ( empty( $body[ $key ] ) || ! is_array( $body[ $key ] ) ) {
				continue;
			}

			foreach ( $body[ $key ] as $i => $breed ) {
				$label = $breed['label'] ?? '';
				if ( ! self::has_hebrew( $label ) ) {
					continue; // Already English (or empty) -- leave alone.
				}
				$lookup  = self::normalize( $label );
				$english = $map[ $lookup ] ?? null;

				// Retry without a leading species word ("כלב "/"חתול ") or a
				// leading definite article, which the API's labels may carry.
				if ( null === $english ) {
					$stripped = preg_replace( '/^(כלב|חתול)\s+/u', '', $lookup );
					$english  = $map[ $stripped ] ?? ( $map[ preg_replace( '/^ה/u', '', $stripped ) ] ?? null );
				}

				if ( null !== $english ) {
					// Hebrew first, English alongside: 'פודל (Poodle)'.
					$body[ $key ][ $i ]['label'] = $label . ' (' . $english . ')';
					$body[ $key ][ $i ]['label_en'] = $english;
				} else {
					$untranslated[] = array( 'type' => $key, 'id' => $breed['id'] ?? null, 'label' => $label );
				}
			}

			// The API sorts by its own (Hebrew) label; re-sort by what the
			// English name where known. Hebrew-only leftovers sort after Latin ones.
			usort(
				$body[ $key ],
				static function ( $a, $b ) {
					$la = (string) ( $a['label_en'] ?? $a['label'] ?? '' );
					$lb = (string) ( $b['label_en'] ?? $b['label'] ?? '' );
					return strcasecmp( $la, $lb );
				}
			);
		}

		if ( $untranslated && Govetty_Settings::is_logging_enabled() && ! get_transient( 'govetty_breeds_untranslated_logged' ) ) {
			set_transient( 'govetty_breeds_untranslated_logged', 1, DAY_IN_SECONDS );
			Govetty_Logger::log( 'breeds_untranslated', array( 'count' => count( $untranslated ), 'breeds' => $untranslated ) );
		}

		return $body;
	}
}
