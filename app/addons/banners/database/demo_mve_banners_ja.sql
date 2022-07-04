REPLACE INTO ?:banner_descriptions (`banner_id`, `banner`, `url`, `description`, `lang_code`) VALUES(7, 'ギフト券', 'gift_certificates.add', '', 'ja');
REPLACE INTO ?:banner_descriptions (`banner_id`, `banner`, `url`, `description`, `lang_code`) VALUES(9, '配送に関するご案内', 'index.php?dispatch=pages.view&page_id=20', '', 'ja');


UPDATE ?:banner_descriptions SET banner='マーケットプレイスデモへようこそ', url='index.php?dispatch=pages.view&page_id=24', description='' WHERE banner_id=6 AND lang_code='ja';
UPDATE ?:banner_descriptions SET banner='Acme Corporation: ロイヤリティプログラムご参加のご案内', url='index.php?dispatch=pages.view&page_id=25', description='' WHERE banner_id=8 AND lang_code='ja';
UPDATE ?:banner_descriptions SET banner='出品者になると取引手数料無料です', url='index.php?dispatch=pages.view&page_id=27', description='' WHERE banner_id=16 AND lang_code='ja';
UPDATE ?:banner_descriptions SET banner='取引手数料無料', url='companies.apply_for_vendor&plan_id=2', description='' WHERE banner_id=18 AND lang_code='ja';
UPDATE ?:banner_descriptions SET url='index.php?dispatch=products.view&product_id=248' WHERE banner_id=17 AND lang_code='ja';
UPDATE ?:banner_descriptions SET banner='X-Box モバイル', url='index.php?dispatch=products.view&product_id=248', description='' WHERE banner_id=20 AND lang_code='ja';
UPDATE ?:banner_descriptions SET banner='Acme モバイル', url='index.php?dispatch=pages.view&page_id=25', description='' WHERE banner_id=21 AND lang_code='ja';
UPDATE ?:banner_descriptions SET banner='マーケットプレイスデモモバイル', url='index.php?dispatch=pages.view&page_id=24', description='' WHERE banner_id=22 AND lang_code='ja';
