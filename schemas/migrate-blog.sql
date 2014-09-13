INSERT INTO wechsler_adze.blog_post
SELECT
  (itemid * 256) + anum AS id,
  `time`,
  subject,
  `event`             AS body,
  'public'            AS security
FROM wechsler_phase.lj_post
WHERE
  security != 'private';
