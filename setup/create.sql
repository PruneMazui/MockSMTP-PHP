/* CREATE DATABASE */
DROP DATABASE IF EXISTS mock_smtp;
CREATE DATABASE mock_smtp;

/** DROP TABLE **/
DROP TABLE IF EXISTS mock_smtp.t_mail;
DROP TABLE IF EXISTS mock_smtp.t_mail_orig;
DROP TABLE IF EXISTS mock_smtp.t_mail_from;
DROP TABLE IF EXISTS mock_smtp.t_mail_to;
DROP TABLE IF EXISTS mock_smtp.t_mail_header;
DROP TABLE IF EXISTS mock_smtp.t_mail_file;
DROP TABLE IF EXISTS mock_smtp.t_mail_file_data;
DROP TABLE IF EXISTS mock_smtp.t_log;


/** CREATE TABLE **/
CREATE TABLE mock_smtp.t_mail (
  mail_id       INTEGER       NOT NULL auto_increment COMMENT 'メールID',
  subject       VARCHAR(511)  NOT NULL DEFAULT ''     COMMENT 'タイトル',
  content_type  VARCHAR(64)   NOT NULL DEFAULT ''     COMMENT 'Content-Type',
  body          TEXT          NOT NULL                COMMENT '本文',
  message_id    VARCHAR(255) CHARACTER SET latin1 NOT NULL DEFAULT ''     COMMENT 'メッセージID',
  receive_count SMALLINT      NOT NULL DEFAULT 0      COMMENT '受信回数',
  error_flg     BOOLEAN       NOT NULL DEFAULT 0      COMMENT 'エラーフラグ',
  error_msg     VARCHAR(255)  NOT NULL DEFAULT ''     COMMENT 'エラーメッセージ',
  error_trace   VARCHAR(1024) NOT NULL DEFAULT ''     COMMENT 'エラートレース',
  receive_date  DATETIME      NOT NULL                COMMENT '受信日時',

  PRIMARY KEY (mail_id)
) ROW_FORMAT=DYNAMIC COMMENT='メール';

CREATE TABLE mock_smtp.t_mail_orig (
  mail_id INTEGER  NOT NULL COMMENT 'メールID',
  content LONGBLOB NOT NULL COMMENT '内容',

  PRIMARY KEY (mail_id)
) ROW_FORMAT=DYNAMIC COMMENT='メール_オリジナル';

CREATE TABLE mock_smtp.t_mail_from (
   mail_id INTEGER       NOT NULL            COMMENT 'メールID',
   seq     SMALLINT      NOT NULL            COMMENT '連番',
   address VARCHAR (255) NOT NULL DEFAULT '' COMMENT 'FROMアドレス',
   
   PRIMARY KEY (mail_id, seq)
) ROW_FORMAT=DYNAMIC COMMENT='メール_送信元';

CREATE TABLE mock_smtp.t_mail_to (
   mail_id INTEGER       NOT NULL            COMMENT 'メールID',
   seq     SMALLINT      NOT NULL            COMMENT '連番',
   type    SMALLINT      NOT NULL DEFAULT 0  COMMENT 'タイプ',
   address VARCHAR (255) NOT NULL DEFAULT '' COMMENT 'TOアドレス',
   
   PRIMARY KEY (mail_id, seq)
) ROW_FORMAT=DYNAMIC COMMENT='メール_送信先';

CREATE TABLE mock_smtp.t_mail_header (
   mail_id    INTEGER       NOT NULL            COMMENT 'メールID',
   seq        SMALLINT      NOT NULL            COMMENT '連番',
   header_seq SMALLINT      NOT NULL            COMMENT 'ヘッダ連番',
   name       VARCHAR(255)  NOT NULL DEFAULT '' COMMENT '名前',
   content    VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '内容',
   
   PRIMARY KEY (mail_id, seq, header_seq)
) ROW_FORMAT=DYNAMIC COMMENT='メール_ヘッダ';

CREATE TABLE mock_smtp.t_mail_file (
   mail_id      INTEGER       NOT NULL            COMMENT 'メールID',
   seq          SMALLINT      NOT NULL            COMMENT '連番',
   content_type VARCHAR(64)   NOT NULL DEFAULT '' COMMENT 'Content-Type',
   filename     VARCHAR(1024) NOT NULL DEFAULT '' COMMENT 'ファイル名',
   filesize     INTEGER       NOT NULL DEFAULT 0  COMMENT 'ファイルサイズ',
   filehash     VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'ファイルハッシュ値',
   width        INTEGER       NOT NULL DEFAULT 0  COMMENT '横幅',
   height       INTEGER       NOT NULL DEFAULT 0  COMMENT '縦幅',
   
   PRIMARY KEY (mail_id, seq)
) ROW_FORMAT=DYNAMIC COMMENT='メール_添付ファイル';

CREATE TABLE mock_smtp.t_mail_file_data (
   mail_id INTEGER  NOT NULL COMMENT 'メールID',
   seq     SMALLINT NOT NULL COMMENT '連番',
   data    LONGBLOB NOT NULL COMMENT 'データ',
   
   PRIMARY KEY (mail_id, seq)
) ROW_FORMAT=DYNAMIC COMMENT='メール_添付ファイル_データ';


/** CREATE INDEX FOREIGN KEY **/

ALTER TABLE mock_smtp.t_mail_orig      ADD CONSTRAINT fk_mail_orig_01      FOREIGN KEY (mail_id)      REFERENCES mock_smtp.t_mail(mail_id)           ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE mock_smtp.t_mail_from      ADD CONSTRAINT fk_mail_from_01      FOREIGN KEY (mail_id)      REFERENCES mock_smtp.t_mail(mail_id)           ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE mock_smtp.t_mail_to        ADD CONSTRAINT fk_mail_to_01        FOREIGN KEY (mail_id)      REFERENCES mock_smtp.t_mail(mail_id)           ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE mock_smtp.t_mail_header    ADD CONSTRAINT fk_mail_header_01    FOREIGN KEY (mail_id)      REFERENCES mock_smtp.t_mail(mail_id)           ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE mock_smtp.t_mail_file      ADD CONSTRAINT fk_mail_file_01      FOREIGN KEY (mail_id)      REFERENCES mock_smtp.t_mail(mail_id)           ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE mock_smtp.t_mail_file_data ADD CONSTRAINT fk_mail_file_data_01 FOREIGN KEY (mail_id, seq) REFERENCES mock_smtp.t_mail_file(mail_id, seq) ON UPDATE CASCADE ON DELETE CASCADE;
CREATE UNIQUE INDEX idx_mail_01 ON mock_smtp.t_mail (message_id);
