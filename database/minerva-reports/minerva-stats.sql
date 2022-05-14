/*
 * Copyright (c) 2022  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

-- Language: sqlite

select (select count(*) from audios)                                                       as total,
       (select count(*) from audios where extra_info IS NOT NULL)                          as total_dz,
       (select count(*) from audios where source_id LIKE '%.flac')                         as is_flac,
       (select count(*) from audios where extra_info LIKE '%LYRICS_COPYRIGHTS%')           as has_lyrics,
       (select count(*) from audios where extra_info LIKE '%LYRICS_SYNC_JSON%')            as has_synced_lyrics,
       ROUND((CAST((select count(*)
                    from audios
                    where extra_info IS NOT NULL) AS REAL) /
              (select count(*) from audios)) * 100, 1) || '%'                              as is_dz_percent,
       ROUND((CAST((select count(*)
                    from audios
                    where source_id LIKE '%.flac') AS REAL) /
              (select count(*) from audios where extra_info IS NOT NULL)) * 100, 1) || '%' as is_flac_percent,
       ROUND((CAST((select count(*)
                    from audios
                    where extra_info LIKE '%LYRICS_COPYRIGHTS%') AS REAL) /
              (select count(*) from audios where extra_info IS NOT NULL)) * 100, 1) || '%' as has_lyrics_percent,
       ROUND((CAST((select count(*)
                    from audios
                    where extra_info LIKE '%LYRICS_SYNC_JSON%') AS REAL) /
              (select count(*) from audios where extra_info IS NOT NULL)) * 100, 1) || '%' as has_synced_lyrics_percent;