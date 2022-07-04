REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '申請済み' as description,
    '返品申請を受け付けました。' as email_subj,
    '返品申請を受け付けました。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '承認済み' as description,
    '返品申請が承認されました。' as email_subj,
    'お客様の返品申請が承認されました。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '却下' as description,
    '返品申請が却下されました。' as email_subj,
    'お客様の返品申請は却下されました。詳細はお手数ですがショップ管理者までお問い合わせください。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '完了' as description,
    '返品処理が完了しました。' as email_subj,
    'お客様の返品申請について処理を完了しました。またのご利用をお待ちしております。' as email_header,
    'ja' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';