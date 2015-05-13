# MockSMTP PHP

## 概要

リレーされたすべてのメールをローカルに受信し、WebUIとして表示します

* プログラムからメールを送信するときに実際にメールを受信しなくても確認ができます。
* HTMLメールや添付ファイルがあっても正しく受信可能です。
* 添付ファイルの中身もダウンロード可能です。
* 携帯の絵文字が含まれたメールにも一部対応。
* メールの検索が可能です。


## 注意事項

* 個人の開発用を想定しています。
* その他もろもろ使用する人の自己責任でお願いします。

## 動作環境

* php >= 5.4
* MySQL >= 5.5
* php-mbstring
* php-pdo
* php-gd
* php-pecl-mailparse
* ホックスクリプトが動かせるMTA（Postfixを想定）
* 適当なWebサーバ（apache想定）

## 設置手順(MockSMTP配置サーバ）

CentOS6系での導入例。
/opt/mocksmtp以下にチェックアウト。
チェックアウトしたディレクトリを基準としてます。

### Composer

	curl -s https://getcomposer.org/installer | php
    php composer.phar install


### DB設定

    vi /etc/my.cnf
    %>>>
    character-set-server = utf8mb4
    collation-server = utf8mb4_general_ci
    <<<%

    service mysqld reload
    mysql < setup/create.sql

### apache設定

ドキュメントルートを html/ 以下にし、mod_rewrite を有効にする。

### postfix設定

適時書き換え

    vi /etc/postfix/main.cf

    %>>>
    ## 外からメールの受信を許可
    inet_interfaces = all

    ## エイリアスの変更
    alias_maps           = hash:/etc/postfix/aliases
    alias_database       = hash:/etc/postfix/aliases

    ## 知らないローカルユーザーを拒否しない
    local_recipient_maps =

    ## ローカル配送で不明なユーザへのメールは mocksmtp へ送る
    luser_relay = mocksmtp

    ## トランスポートマップを指定
    transport_maps = hash:/etc/postfix/transport
    <<<%

    vi /etc/postfix/transport
    %>>>
    *  local:
    <<<%

メール受信時のプログラム動作を設定する

    vi /etc/postfix/aliases
	%>>>
    mocksmtp: "|/usr/bin/php /opt/mocksmtp/script/run.php"
    <<<%

設定内容を反映する

    newaliases
    postmap /etc/postfix/transport
    service postfix restart

mailコマンドでメールを送り、正しく動作できているか確認する。

## 設置手順（メール送信元）

mtaがpostfixになっているか確認（なっていなければpostfixに変更する）

	alternatives --config mta

    vi /etc/postfix/main.cf
    %>>>
	transport_maps = hash:/etc/postfix/transport
    <<<%

全部のドメインをmocksmtpサーバにリレーする

    vi /etc/postfix/transport
    %>>>
    *   smtp:[MockSMTP配置サーバのIPアドレス]
    <<<%

transport.dbの作成

    postmap /etc/postfix/transport
    service postfix restart

mailコマンドでメールを送り、正しく動作できているか確認する。


