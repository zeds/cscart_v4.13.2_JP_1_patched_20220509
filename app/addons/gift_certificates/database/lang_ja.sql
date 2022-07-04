REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '保留' as description,
    'ギフト券発行のお知らせ' as email_subj,
    'ギフト券が発行されました。有効化されるまで今しばらくお待ちください。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '有効' as description,
    'ギフト券有効化のお知らせ' as email_subj,
    'ギフト券が有効化されました。今すぐお使いいただけます。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '無効' as description,
    'ギフト券無効化のお知らせ' as email_subj,
    'ギフト券が無効化されました。 お手数ですがショップ管理者までお問い合わせください。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '利用済み' as description,
    'ギフト券ご利用通知' as email_subj,
    'ギフト券が使用されました。 当店をご利用いただき、誠にありがとうございます。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';