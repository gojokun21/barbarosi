<?php
/**
 * Catalogul demo: produsele create de `wp barbarosi seed` (src/DemoSeeder.php).
 *
 * TEMPORAR — pozele vin de pe CDN-ul dailypaperclothing.com (DemoContent::CDN_FILES) și sunt
 * copiate în Media Library la seed. Când avem produse reale, fișierul acesta dispare.
 *
 * Cheia fiecărui produs e și slug-ul lui. Prima culoare dintr-un `group` apare prima în swatch-uri.
 * Prima poză = imaginea principală, a doua = imaginea de hover din card.
 */

/**
 * Descriere de produs: paragraf + listă cu detalii + nota de mărime.
 *
 * @param string[] $points
 */
$describe = static function ( string $intro, array $points, bool $size_note = true ): string {
	$items = implode( '', array_map( static fn( string $point ): string => "<li>{$point}</li>", $points ) );
	$html  = "<p>{$intro}</p>\n<ul>{$items}</ul>";

	return $size_note ? "{$html}\n<p>Femeile pot prefera o mărime mai mică.</p>" : $html;
};

$apparel = [ 'xs', 's', 'm', 'l', 'xl', 'xxl' ];

$dias_puffer = $describe(
	'Geacă puffer cu croială relaxată, din material ripstop, cu mâneci raglan pentru libertate de mișcare. Logo-ul Dias e imprimat în relief pe piept, iar gluga are șnur cu capete personalizate.',
	[ 'Croială relaxată', 'Mâneci raglan', 'Logo Dias imprimat în relief pe piept', 'Glugă cu șnur și capete cu logo', 'Buzunare laterale și buzunar interior', 'Material ripstop' ]
);

$raglan = $describe(
	'Bluză cu mânecă lungă, mâneci raglan și cusături laterale pentru o siluetă relaxată, sport. Spălarea finală dă materialului french terry un aspect purtat, completat de detalii uzate pentru un look vintage.',
	[ 'Mâneci raglan cu cusături laterale', 'Grafică pe piept', 'Patch-uri cusute „Dias Arch”', 'Detalii uzate la guler, mâneci și tiv', 'Finisaj stone-washed' ]
);

$chapka = $describe(
	'Căciulă chapka din piele, căptușită cu blană ecologică moale, cu monogramă embosată pe toată suprafața.',
	[ 'Monogramă embosată pe toată suprafața', 'Închidere cu șnur', 'Căptușeală din blană ecologică', 'Exterior 100% piele' ],
	false
);

return [
	// Atributul `pa_culoare` (slug => nume). Nuanța exactă a swatch-ului e pe fiecare produs (`hex`).
	'colors'     => [
		'verde'       => 'Verde',
		'negru'       => 'Negru',
		'portocaliu'  => 'Portocaliu',
		'gri-deschis' => 'Gri deschis',
		'albastru'    => 'Albastru',
		'alb'         => 'Alb',
		'maro'        => 'Maro',
		'visiniu'     => 'Vișiniu',
		'verde-army'  => 'Verde army',
		'bleu'        => 'Bleu',
	],

	// Atributul `pa_marime`, în ordinea de afișare.
	'sizes'      => [
		'xs'           => 'XS',
		's'            => 'S',
		'm'            => 'M',
		'l'            => 'L',
		'xl'           => 'XL',
		'xxl'          => 'XXL',
		'marime-unica' => 'Mărime unică',
	],

	// Categorii (slug => [ nume, părinte ]).
	'categories' => [
		'topuri'             => [ 'Topuri', null ],
		'hanorace-pulovere'  => [ 'Hanorace & pulovere', 'topuri' ],
		'tricouri'           => [ 'Tricouri', 'topuri' ],
		'camasi'             => [ 'Cămăși', 'topuri' ],
		'bluze-maneca-lunga' => [ 'Bluze cu mânecă lungă', 'topuri' ],
		'geci'               => [ 'Geci', 'topuri' ],
		'tricotaje'          => [ 'Tricotaje', 'topuri' ],
		'pantaloni'          => [ 'Pantaloni', null ],
		'blugi'              => [ 'Blugi', 'pantaloni' ],
		'pantaloni-cargo'    => [ 'Pantaloni cargo', 'pantaloni' ],
		'accesorii'          => [ 'Accesorii', null ],
		'sepci-caciuli'      => [ 'Șepci & căciuli', 'accesorii' ],
	],

	/*
	 * Ținute: produsele din aceeași ținută apar unul la altul în „Combină cu” (upsells WooCommerce).
	 */
	'outfits'    => [
		[ 'camasa-denim-croc-jacquard-albastru', 'tricou-origami-shield-alb', 'blugi-oversized-croc-jacquard-albastru' ],
		[ 'caciula-chapka-monogram-maro', 'pulover-tricotat-shield-fermoar-negru', 'pantaloni-cargo-washed-verde-army' ],
		[ 'geaca-puffer-ayan-monogram-verde-army', 'camasa-dungi-texturate-bleu', 'pantaloni-cargo-washed-verde-army' ],
		[ 'geaca-puffer-dias-raglan-verde', 'hanorac-boxy-stay-informed-negru', 'tricou-vintage-stay-informed-gri-deschis', 'blugi-oversized-croc-jacquard-albastru' ],
		[ 'geaca-puffer-dias-raglan-negru', 'bluza-raglan-contrast-verde', 'pantaloni-cargo-washed-verde-army', 'caciula-chapka-monogram-visiniu' ],
		[ 'bluza-raglan-contrast-portocaliu', 'blugi-oversized-croc-jacquard-albastru', 'caciula-chapka-monogram-maro' ],
	],

	/*
	 * Produse. Câmpuri:
	 *   name, sku, price, sale_price?, categories[], color (slug din `colors`), hex (swatch),
	 *   group? (produse identice în alte culori), sizes[], sold_out[]?, size_guide? (tops|bottoms),
	 *   model? (descrierea scurtă), new? (tag „Nou”), description, images[] (fișiere de pe CDN)
	 */
	'products'   => [
		'geaca-puffer-dias-raglan-verde'           => [
			'name'        => 'Geacă puffer Dias cu mâneci raglan — verde',
			'sku'         => 'BRB-FW26-001',
			'price'       => 1599,
			'categories'  => [ 'topuri', 'geci' ],
			'color'       => 'verde',
			'hex'         => '#73a073',
			'group'       => 'puffer-dias-raglan',
			'sizes'       => $apparel,
			'sold_out'    => [ 'xxl' ],
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 185 cm și poartă mărimea M.',
			'new'         => true,
			'description' => $dias_puffer,
			'images'      => [
				'P260818123721_Fullbody_Jpg_High_res_5.jpg',
				'RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_GRASSWHOPER_Front.jpg',
				'RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_GRASSWHOPER_Back.jpg',
			],
		],
		'hanorac-boxy-stay-informed-negru'         => [
			'name'        => 'Hanorac boxy cu fermoar Stay Informed — negru',
			'sku'         => 'BRB-FW26-002',
			'price'       => 849,
			'categories'  => [ 'topuri', 'hanorace-pulovere' ],
			'color'       => 'negru',
			'hex'         => '#1c1c1c',
			'sizes'       => $apparel,
			'sold_out'    => [ 'xs' ],
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 183 cm și poartă mărimea M.',
			'new'         => true,
			'description' => $describe(
				'Hanorac cu fermoar și croială boxy, din fleece de bumbac periat, moale la atingere. Imprimeu serigrafiat „Stay Informed” pe piept și cursor de fermoar cu logo.',
				[ 'Croială boxy', 'Imprimeu serigrafiat „Stay Informed” pe piept', 'Buzunar tip cangur', 'Cursor de fermoar cu logo', 'Fleece de bumbac periat' ]
			),
			'images'      => [
				'P260818120630_Top_Jpg_High_res_4.jpg',
				'VINTAGE_STAY_INFORMED_BOXY_HOODIE_BLACK_Front.jpg',
				'VINTAGE_STAY_INFORMED_BOXY_HOODIE_BLACK_Back.jpg',
			],
		],
		'bluza-raglan-contrast-portocaliu'         => [
			'name'        => 'Bluză raglan cu contrast — portocaliu',
			'sku'         => 'BRB-FW26-003',
			'price'       => 429,
			'categories'  => [ 'topuri', 'bluze-maneca-lunga' ],
			'color'       => 'portocaliu',
			'hex'         => '#f0a830',
			'group'       => 'bluza-raglan-contrast',
			'sizes'       => $apparel,
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 186 cm și poartă mărimea M.',
			'new'         => true,
			'description' => $raglan,
			'images'      => [
				'P260818112513_Top_Jpg_High_res_3.jpg',
				'CONTRAST_RAGLAN_LS_T-SHIRT_GOLDEN_ORANGE_Front.jpg',
				'CONTRAST_RAGLAN_LS_T-SHIRT_GOLDEN_ORANGE_Back.jpg',
			],
		],
		'tricou-vintage-stay-informed-gri-deschis' => [
			'name'        => 'Tricou vintage Stay Informed — gri deschis',
			'sku'         => 'BRB-FW26-004',
			'price'       => 299,
			'categories'  => [ 'topuri', 'tricouri' ],
			'color'       => 'gri-deschis',
			'hex'         => '#c9c9c9',
			'sizes'       => $apparel,
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 180 cm și poartă mărimea M.',
			'new'         => true,
			'description' => $describe(
				'Tricou din jerseu simplu de bumbac, cu imprimeu serigrafiat „Stay Informed” pe piept, pentru un look de zi cu zi.',
				[ 'Mărime standard', 'Imprimeu serigrafiat pe piept', 'Guler rotund', 'Jerseu de bumbac moale' ]
			),
			'images'      => [
				'P260818102746_Top_Jpg_High_res_12.jpg',
				'VINTAGE_STAY_INFORMED_T-SHIRT_LIGHT_GREY_Front.jpg',
				'VINTAGE_STAY_INFORMED_T-SHIRT_LIGHT_GREY_Back.jpg',
			],
		],
		'geaca-puffer-dias-raglan-negru'           => [
			'name'        => 'Geacă puffer Dias cu mâneci raglan — negru',
			'sku'         => 'BRB-FW26-005',
			'price'       => 1599,
			'categories'  => [ 'topuri', 'geci' ],
			'color'       => 'negru',
			'hex'         => '#504949',
			'group'       => 'puffer-dias-raglan',
			'sizes'       => $apparel,
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 188 cm și poartă mărimea L.',
			'description' => $dias_puffer,
			'images'      => [
				'FW26_-_RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_BlackP2606101205537.jpg',
				'FW26_-_RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_Black_Font_9bb7fa14-8dba-4120-88cd-6092a57a5780.jpg',
				'FW26_-_RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_Black_Back_29b92de4-4a55-44c5-868d-0852e4fe54d2.jpg',
				'FW26_-_RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_Black_Detail_76f593be-2424-43a1-9320-d2b78a386901.jpg',
				'FW26_-_RAGLAN_SLEEVE_DIAS_PUFFER_JACKET_BlackP2606101205531.jpg',
			],
		],
		'bluza-raglan-contrast-verde'              => [
			'name'        => 'Bluză raglan cu contrast — verde',
			'sku'         => 'BRB-FW26-006',
			'price'       => 429,
			'categories'  => [ 'topuri', 'bluze-maneca-lunga' ],
			'color'       => 'verde',
			'hex'         => '#3f5b41',
			'group'       => 'bluza-raglan-contrast',
			'sizes'       => $apparel,
			'sold_out'    => [ 's', 'm' ],
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 186 cm și poartă mărimea M.',
			'description' => $raglan,
			'images'      => [
				'P260818111257_Top_Jpg_High_res_5.jpg',
				'CONTRAST_RAGLAN_LS_T-SHIRT_SPHAGNUM_Front.jpg',
				'CONTRAST_RAGLAN_LS_T-SHIRT_SPHAGNUM_Back.jpg',
			],
		],
		'camasa-denim-croc-jacquard-albastru'      => [
			'name'        => 'Cămașă denim croc jacquard — albastru',
			'sku'         => 'BRB-FW26-007',
			'price'       => 899,
			'categories'  => [ 'topuri', 'camasi' ],
			'color'       => 'albastru',
			'hex'         => '#5b7ea3',
			'sizes'       => $apparel,
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 187 cm și poartă mărimea L.',
			'description' => $describe(
				'Cămașă cu croială regular, din denim fără elastan, cu model croc jacquard țesut pe toată suprafața. Capsele cu logo de pe față și de la manșete completează detaliile.',
				[ 'Mărime standard', 'Model croc jacquard pe toată suprafața', 'Închidere cu capse cu logo', 'Buzunare la piept', 'Capse cu logo la manșete' ]
			),
			'images'      => [
				'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_BlueP2606101637171-12.jpg',
				'FW26_-_CROC_JACQUARD_DENIM_SHIRT_Mid_Blue_Front_d84246b3-063e-4662-8eaa-bad53b32466e.jpg',
				'FW26_-_CROC_JACQUARD_DENIM_SHIRT_Mid_Blue_Back_b4d78067-6986-4b6b-9fab-728538daa57b.jpg',
				'FW26_-_CROC_JACQUARD_DENIM_SHIRT_Mid_Blue_Detail_b0d998d1-3a78-4d45-a64a-17c6e27caedf.jpg',
			],
		],
		'tricou-origami-shield-alb'                => [
			'name'        => 'Tricou Origami Shield — alb',
			'sku'         => 'BRB-FW26-008',
			'price'       => 329,
			'sale_price'  => 249,
			'categories'  => [ 'topuri', 'tricouri' ],
			'color'       => 'alb',
			'hex'         => '#ffffff',
			'sizes'       => $apparel,
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 184 cm și poartă mărimea M.',
			'description' => $describe(
				'Tricou din jerseu de bumbac, cu imprimeu serigrafiat „Origami” cu logo-urile Dias și Shield pe piept și o grafică mare, asortată, pe spate.',
				[ 'Mărime standard', 'Imprimeu „Origami” Dias + Shield pe piept', 'Grafică mare pe spate', 'Jerseu de bumbac moale' ]
			),
			'images'      => [
				'White_Origami_Shield_T-Shirt.jpg',
				'ORIGAMI_SHIELD_T-SHIRT_WHITE_BACK.jpg',
				'ORIGAMI_SHIELD_T-SHIRT_WHITE_FRONT_e7c95b2e-3e8f-4a46-8557-9480e353612f.jpg',
			],
		],
		'blugi-oversized-croc-jacquard-albastru'   => [
			'name'        => 'Blugi oversized croc jacquard — albastru',
			'sku'         => 'BRB-FW26-009',
			'price'       => 799,
			'categories'  => [ 'pantaloni', 'blugi' ],
			'color'       => 'albastru',
			'hex'         => '#5b7ea3',
			'sizes'       => $apparel,
			'sold_out'    => [ 'xs' ],
			'size_guide'  => 'bottoms',
			'model'       => 'Modelul are 187 cm și poartă mărimea L.',
			'description' => $describe(
				'Blugi cu croială oversized, asortați cu cămașa Croc Jacquard — același model țesut în denim fără elastan. Eticheta din piele ecologică și capsele metalice cu logo finisează spatele.',
				[ 'Croială oversized', 'Model croc jacquard pe toată suprafața', 'Închidere cu fermoar și nasture', 'Buzunare rotunjite în față și buzunar pentru monede', 'Etichetă din piele ecologică la spate', 'Capse metalice cu logo' ]
			),
			'images'      => [
				'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_BlueP260610163717_Fullbody_Jpg_High_res_13.jpg',
				'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_Blue_Front_e6f1c52d-03a4-4f4b-91bd-5dadfb0a3e80.jpg',
				'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_Blue_Black_b3da0a2e-5495-4b26-9e77-2453abd9e9b3.jpg',
				'FW26_-_CROC_JACQUARD_OVERSIZED_JEANS_Mid_Blue_Detail_59801d16-474a-4eb6-b3af-68a3869c0931.jpg',
			],
		],
		'caciula-chapka-monogram-maro'             => [
			'name'        => 'Căciulă chapka Monogram — maro',
			'sku'         => 'BRB-FW26-010',
			'price'       => 649,
			'categories'  => [ 'accesorii', 'sepci-caciuli' ],
			'color'       => 'maro',
			'hex'         => '#6b4a3a',
			'group'       => 'chapka-monogram',
			'sizes'       => [ 'marime-unica' ],
			'description' => $chapka,
			'images'      => [
				'FW26_-_MONOGRAM_CHAPKA_MADDER_BROWNP260610122202.jpg',
				'FW26_-_MONOGRAM_CHAPKA_MADDER_BROWN__Front_2.jpg',
				'FW26_-_MONOGRAM_CHAPKA_MADDER_BROWN__Back_2.jpg',
				'FW26_-_MONOGRAM_CHAPKA_MADDER_BROWNP2606101222021.jpg',
				'FW26_-_MONOGRAM_CHAPKA_MADDER_BROWNP2606101222022.jpg',
			],
		],
		'caciula-chapka-monogram-visiniu'          => [
			'name'        => 'Căciulă chapka Monogram — vișiniu',
			'sku'         => 'BRB-FW26-011',
			'price'       => 649,
			'categories'  => [ 'accesorii', 'sepci-caciuli' ],
			'color'       => 'visiniu',
			'hex'         => '#7a1010',
			'group'       => 'chapka-monogram',
			'sizes'       => [ 'marime-unica' ],
			'description' => $chapka,
			'images'      => [
				'FW26_-_MONOGRAM_CHAPKA_MOLEP260610162219_Top_Jpg_High_res_3_copy.jpg',
				'FW26_-_MONOGRAM_CHAPKA_MOLE__Front_2.jpg',
				'FW26_-_MONOGRAM_CHAPKA_MOLE__Back_2.jpg',
			],
		],
		'pulover-tricotat-shield-fermoar-negru'    => [
			'name'        => 'Pulover tricotat Shield cu fermoar — negru',
			'sku'         => 'BRB-FW26-012',
			'price'       => 899,
			'categories'  => [ 'topuri', 'hanorace-pulovere', 'tricotaje' ],
			'color'       => 'negru',
			'hex'         => '#1c1c1c',
			'sizes'       => $apparel,
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 185 cm și poartă mărimea M.',
			'description' => $describe(
				'Pulover cu fermoar, tricotat din fir de poliester amestecat cu fir pufos pentru textură, cu model Shield pe toată suprafața, în față și pe spate. Guler înalt tricotat în coaste și fermoar dublu cu cursor cu logo.',
				[ 'Mărime standard', 'Fermoar dublu cu cursor cu logo', 'Guler înalt, tricotat în coaste', 'Model Shield în față și pe spate', 'Tricot texturat' ]
			),
			'images'      => [
				'FW26_-_SHIELD_KNIT_ZIP_UP_BLACK-BROWN_DETAILP2606101222021.jpg',
				'FW26_-_SHIELD_KNIT_ZIP_UP_BLACK-BROWN_FRONT_14012695-f153-4f9e-9014-21b14bc272b6.jpg',
				'FW26_-_SHIELD_KNIT_ZIP_UP_BLACK-BROWN_BACK_afe0992b-624f-48ee-a981-b4348431c0de.jpg',
				'FW26_-_SHIELD_KNIT_ZIP_UP_BLACK-BROWN_DETAIL_17ba00a7-0582-4391-b8b1-407a2022a947.jpg',
				'FW26_-_SHIELD_KNIT_ZIP_UP_BLACK-BROWN_DETAILP260610122202.jpg',
			],
		],
		'pantaloni-cargo-washed-verde-army'        => [
			'name'        => 'Pantaloni cargo washed — verde army',
			'sku'         => 'BRB-FW26-013',
			'price'       => 799,
			'categories'  => [ 'pantaloni', 'pantaloni-cargo' ],
			'color'       => 'verde-army',
			'hex'         => '#5b5e3c',
			'sizes'       => $apparel,
			'size_guide'  => 'bottoms',
			'model'       => 'Modelul are 182 cm și poartă mărimea M.',
			'description' => $describe(
				'Pantaloni cargo cu croială relaxată, din bumbac spălat, cu buzunare oblice cu clapă pentru un look utilitar clasic. Eticheta Dias din silicon și nasturele cu logo completează detaliile.',
				[ 'Croială relaxată', 'Buzunare oblice cu clapă', 'Buzunar la spate', 'Etichetă Dias din silicon', 'Nasture cu logo', 'Închidere cu fermoar și nasture' ]
			),
			'images'      => [
				'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_GreenP260610102908.jpg',
				'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_Green_Front_feaceee1-f7d6-40d4-8efd-a1dc28bfc6c6.jpg',
				'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_Green_Back_ae553720-85c5-41a1-9aa7-f3bd4070f192.jpg',
				'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_GreenP2606101029081.jpg',
				'FW26_-_WASHED_TWO_POCKET_CARGO_PANTS_GreenP2606101029082.jpg',
			],
		],
		'geaca-puffer-ayan-monogram-verde-army'    => [
			'name'        => 'Geacă puffer Ayan Monogram — verde army',
			'sku'         => 'BRB-FW26-014',
			'price'       => 1699,
			'categories'  => [ 'topuri', 'geci' ],
			'color'       => 'verde-army',
			'hex'         => '#6e6f5c',
			'sizes'       => $apparel,
			'sold_out'    => [ 's', 'xxl' ],
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 189 cm și poartă mărimea L.',
			'description' => $describe(
				'Geacă puffer cu croială boxy și monogramă imprimată pe toată suprafața. Gulerul tip bomber și fermoarul ascuns cu cursor cu logo păstrează silueta curată, iar tivul elastic cu opritor ține de cald.',
				[ 'Croială boxy', 'Monogramă imprimată pe toată suprafața', 'Fermoar ascuns cu cursor cu logo', 'Tiv elastic cu opritor cu logo', 'Buzunare laterale' ]
			),
			'images'      => [
				'FW26_-_AYAN_MONOGRAM_JACKET_Stone_GrayP260610101621.jpg',
				'FW26_-_AYAN_MONOGRAM_JACKET_Stone_Gray_Front_2c4f2ea5-d96a-4460-bb01-65b9b29d04fb.jpg',
				'FW26_-_AYAN_MONOGRAM_JACKET_Stone_Gray_Back_da57944c-bc42-473f-80b4-a25d93679c28.jpg',
			],
		],
		'camasa-dungi-texturate-bleu'              => [
			'name'        => 'Cămașă cu dungi texturate — bleu',
			'sku'         => 'BRB-FW26-015',
			'price'       => 749,
			'categories'  => [ 'topuri', 'camasi' ],
			'color'       => 'bleu',
			'hex'         => '#a9c4dd',
			'sizes'       => $apparel,
			'size_guide'  => 'tops',
			'model'       => 'Modelul are 184 cm și poartă mărimea M.',
			'description' => $describe(
				'Cămașă cu mânecă lungă și croială regular, dintr-un material ușor cu dungi texturate, fluid la atingere. Logo-ul brodat pe buzunarul de la piept e un detaliu discret.',
				[ 'Mărime standard', 'Închidere cu nasturi', 'Logo brodat pe buzunarul de la piept', 'Material ușor cu dungi texturate' ]
			),
			'images'      => [
				'FW26_-_TEXTURED_STRIPE_LS_SHIRT_LIGHT_BLUEP2606101232331.jpg',
				'FW26_-_TEXTURED_STRIPE_LS_SHIRT_LIGHT_BLUE_FRONT_06807392-372a-4e8c-a771-8d0f9dcef7ce.jpg',
				'FW26_-_TEXTURED_STRIPE_LS_SHIRT_LIGHT_BLUE_BACK_cef84506-7b39-4724-bb4b-63045342c4a7.jpg',
				'FW26_-_TEXTURED_STRIPE_LS_SHIRT_LIGHT_BLUE_DETAIL_aa0098da-91c6-4d8f-9938-be7f326d003a.jpg',
				'FW26_-_TEXTURED_STRIPE_LS_SHIRT_LIGHT_BLUEP260610123233.jpg',
			],
		],
	],
];
