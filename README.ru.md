Принцип работы:

Тоссер hpt позволяет написать свои процедуры на perl, которые могут исполнятся в момент тоссинга почты.
Данные процедуры описываются в файле filter.pl. Прилагаемый в данном дистрибутиве filter.pl, складывает всё проходящую через тоссер почту в каталог в виде xml. 

Скрипт xml2sql.pl засасывает все эти xml в базу mysql в таблицу messages. 

Скрипт fastlink.php проходится по этой таблице и строит дерево тредов.

Веб-морда, написанная на php работает только с mysql-базой. Позволяет читать из нее письма (в виде тредов и отдельных сообщений), позволяет "подписываться" на конференции (фактически - добавляет их в список в левом столбце) и т.д. При написании пользователем письма, это письмо ложится в таблицу outbox.

Для отправки писем из outbox, используется скрипт sql2pkt.pl. Этот скрипт создает в каталоге inbound пакет, который после тоссинга отправляется по назначению стандартными средствами.

Отдельно сбоку может быть установлен поисковый движок sphinx. Очень полезная штука, работающая значительно быстрее полнотекстового поиска mysql, и умеющая (при правильном конфиге) корректно переживать замену русской Н на английскую H.

Каждый пользователь системы получает полноценный поинтовый адрес. Как следствие, ему доступно полноценное пользование нетмайлом. Вся эта система никак не мешает работе фидошного софта. Так что можно просто продублировать в таблице users всех своих поинтов, и каждый из них сможет читать/писать еще и через веб-интерфейс. Аналогично, можно продублировать любого пользователя, зарегистрированного в веб-морде, в конфигах своего тоссера и мейлера. Поскольку весь нетмайл, приходящий на любого поинта, всё равно складывается в outbound, то этот пользователь получит весь свой нетмайл при первом же коннекте мейлером.

Данная версия wfido проверена и работает на следующих конфигурациях:

Ubuntu Linux 10.04.4:
- perl 5.10.1
- php 5.3.2
- mysql 5.1.41
- hpt 1.4.0
- sphinxsearch 2.0.6

FreeBSD 9.1:
- perl 5.12.4
- php 5.4.10
- mysql 	5.1.67
- hpt 1.9
- sphinxsearch 2.0.6_1

Требует следующие перловые модули:
- DBD::mysql
- Digest::Perl::MD5 (в случае FreeBSD модуль называется Digest::MD5, обязательно см п.6.2)
- FTN::Pkt

Первые два как правило уже установлены, последний можно взять здесь:
http://cpan.uwinnipeg.ca/cpan/authors/id/K/KE/KEU/FTN-Pkt-1.02.tar.gz
(или поставить через CPAN, как всегда)

В php стоит включить always_populate_raw_post_data. Как правило, это можно сделать через .htaccess (который уже есть в комплекте). Если не поможет, то можно поправить в php.ini:
```
; Always populate the $HTTP_RAW_POST_DATA variable.
always_populate_raw_post_data = On
```
Кроме того, php должен быть в состоянии посылать почту. Используется стандартная функция mail(). В общем случае, для ее нормального функционирования должен быть установлен и корректно настроен какой-либо MTA (sendmail, ssmtp и т.д.). Иначе не будет работать активация аккаунтов.


Установка:

В моей системе фидошный софт установлен в каталог /home/fidonet/. Все бинарники лежат в каталоге /home/fidonet/bin, конфиги hpt лежат в каталоге /home/fidonet/etc/fido, inbound находится в /home/fidonet/var/fidonet/inbound (так уж исторически сложилось). 

Все настройки приведены на примере адреса 2:5020/1519

1. Настраиваем тоссер.

1.1. Убеждаемся, что hpt собран с поддержкой perl. Если нет, то пересобираем (необходимо исправить в huskymak.cfg строку PERL=0 на PERL=1).

1.2. Кладем filter.pl в один каталог с конфигами hpt и прописываем путь к этому файлу в конфиге hpt. В моём случае это строка в файле /home/fidonet/etc/fido/config.:

```
HptPerlFile     /home/fidonet/etc/fido/filter.pl
```

В том же файле прописываем самого себя себе линком (иначе не будет работать отсылка писем из веб-морды):

```
link loopback
aka 2:5020/1519
allowemptypktpwd on
```
Для пущего секьюрити, можно указать и пароль на пакет. Тогда его же надо будет указать в переменной my_tech_link_password в скрипте sql2pkt.pl. В дистрибутивном файле эта переменная есть, но она пустая.

1.3. Создаем каталог, в который будут складываться xml и прописываем путь к нему в filter.pl. В моём случае это каталог /home/fidonet/var/fidonet/xml . Прописать путь к каталогу надо в двух местах, один раз в процедуре filter, другой раз в процедуре scan. После исправления обе строки должны выглядеть примерно так:
```perl
open (XML,">/home/fidonet/var/fidonet/xml/$random_string.xml");
```

Так же следует создать каталог /home/fidonet/var/fidonet/xml/archive. Поскльку сейчас мы только тестируем всю эту гирлянду, то будет очень обидно при неаккуратном ковырянии в базе, потерять уже имеющиеся в ней письма. Поэтому сейчас в xml2sql.pl есть строчка, которая вместо того, чтобы удалять xml, перекладывает его в этот каталог. В случае, если придется поднимать свежий дамп (а он наверняка поменяется к следующей версии), можно будет просто переложить все эти файла из архива обратно в
/home/fidonet/var/fidonet/xml/ и снова заполнить ими базу.

1.4. Получаем почту, тоссим, проверяем, стали ли после этого в каталоге /home/fidonet/var/fidonet/xml/ появляться файлы с расшерением xml. Каждое входящее письмо - один такой файл.


2. Создаём базу и настраиваем скрипты, которые будут ее заполнять.

2.1. Открываем файл с шаблоном базы - dump_install.sql. При необходимости правим в начале файла имя базы. Обязательно правим логин и пароль на доступ к базе.
В строке 168 правим значение AUTO_INCREMENT на то, с которого хотим начать нумерацию регистрирующихся пользователей. Сейчас там стоит 100. Соответственно, первый зарегистрировавшийся станет поинтом 2:5020/1519.101

Сейчас надо решить, что будет использоваться для поиска. Если не хочется заморачиваться со sphinx’ом (хотя он стоит того), то можно использовать встроенный в mysql полнотекстовый поиск. Для этого надо раскомментировать последню строку дампа (убрать перед ней «-- » )

Поднимаем этот дамп:
mysql -u root -p < dump_install.sql
На запрос ввести пароль, вводим рутовый пароль от mysql. Если рутовый пароль пустой, то сейчас самое время его поменять.

2.2 Кладем куда-нибудь файл xml2sql.pl. Правим в начале файла переменные на свои:
```perl
$xml_spool="/home/fidonet/var/fidonet/xml";
$sql_base="wfido";
$sql_user="wfido";
$sql_pass="PASSWORD";
```

2.3. Запускаем xml2sql.pl и смотрим в базу, появились ли там новые письма:
$ /home/fidonet/bin/xml2sql.pl
$ echo select hash from messages | mysql -u root -p wfido
Слово wfido в примере - это имя базы. Если на экран набежало несколько строчек вроде "ecfb913eff15847bcb7b27970666b4fe", то письма в базу засасываются нормально.

3. Поднимаем веб-морду

3.1 Копируем содержимое каталога htdocs куда-нибудь в место, доступное через веб (в моем случае - /var/www/wfido). Правим config.php. Там, в общем-то, всё понятно:

$webroot - это путь к папке с файлами от корня виртуалхоста веб-сервера.
$adminpoint - поинтовый адрес, которому будет доступна админка. По-умолчанию - 1.
Имена остальных переменных так же говорят сами за себя.

3.2 Логинимся и настраиваем.
Начиная с этого момента веб-морда работает и пускает под логином 1 и паролем PASSWORD
Заходим в веб-интерфейс, логинимся.
Лезем в settings->персональные данные.
Меняем данные на актуальные. Не забываем сменить пароль.
Идем в Admin->группы. Одна группа там уже есть - netmail. Надо добавить несколько новых. У меня такие группы:
     Свои локалки
     Чужие локалки
     Эхи только для чтения
     Прочие эхи
Лезем в Admin->настройки по умолчанию. Выставляем права для свежесоздаваемых юзеров. например:
     Netmail - premoderated
     Свои локалки - read-write
     Чужие локалки - deny
     Эхи только для чтения - read
     Прочие эхи - premoderated
Ставим галочку "subscribe" около "Свои локалки". Жмем сохранить.
С этого момента все свежесозаваемые пользователи будут автоматически подписаны на все эхи из группы "Свои локалки"
Лезем в Admin->пользователи и выставляем права для единственного пока существующего пользователя - для себя.

Эхоконференция автоматически создается после того, как в нее упадет хотя бы одно письмо. Для того, чтобы эхоконференция стала доступна для подписки, необходимо назначить ей группу (через Admin->Эхоконференции). Имеющие доступ к этой группе хотя бы на чтение, смогут подписаться. Имеющие доступ на запись, смогут написать. Имеющие доступ с премодерцией, смогут отправить письмо на премодерацию. Письма на премодерацию можно увидеть, зайдя в Admin->премодерация. Оттуда же можно либо 
проапрувить письмо, либо реджектнуть (юзеру нетмайлом высылается соответствующее уведомление).

Теперь можно попытаться написать что-либо в эху. Тогда нам уже будет, на чем потестировать отправку в следующем пункте.
(hint: если включили для себя премодерцию, то надо ткнуть на admin (справа вверху) и нажать approve на своём письме)


3.3 Настраиваем поиск
3.3.1 Если мы выбрали в качестве поисквика встроенный в mysql fulltext search (и раскомментировали соответствующую строку в дампе), то надо удалить из htdocs файл search.php и переименовать там же search_mysql.php в search.php. На этом настройка поиска закочена и уже должна работать.

3.3.2 Если мы выбрали в качестве поисковика sphinx, то надо во-первых его установить. В Ubuntu и debian есть в стандартных репозитариях, во FreeBSD есть в портах. Более свежие версии есть на официальном сайте http://sphinxsearch.com/.

В дистрибутиве wfido в каталоге sphix лежат три файла:
- sphinx.conf - конфиг для сфинкса. в этом файле надо поменять как минимум sql_user и sql_pass на соответствующие действительности и скопировать файл вместо конфига сфинкса.
- crontab - строки, которые надо добавить в крон
- _index.sh - скрипт для индексации, который надо положить в /usr/local/bin

Запускаем один раз полную индексацию:
/usr/bin/indexer messages --quiet --rotate
и убеждаемся, что в /var/lib/sphinxsearch/data/ появились файлы индекса. Если они есть, то всё в порядке, сфинкс завелся. 

Лишний файл serach_mysql.php можно удалить.
Логи индексатора сфинкса можно посмотреть здесь: /var/log/sphinxsearch/searchd.log
Логи всех поисковых запросов - здесь: /var/log/sphinxsearch/query.log

4. Настраиваем отправку писем.
Смотрим в sql2pkt.pl:
```perl
$inbound='/home/fidonet/var/fidonet/inbound';
$mynode='2:5020/1519';
$my_tech_link='2:5020/1519';
$my_tech_link_password='';

$sql_base="wfido";
$sql_user="wfido";
$sql_pass="PASSWORD";
```
Эти переменные надо подправить так, чтобы они соответствовали действительности. $my_tech_link_password надо устанавливать в том случае, если в пункте 1.2 был указан пароль для loopback-линка После чего можно запустить этот скрпит и увидеть, как он вынет из базы письмо, написанное в предыдущем пункте, и положит его в inbound в виде pkt. Теперь можно запускать тоссер - он отправит письмо в эху.

5. Автоматизация.
Можно просто раз в 5 минут запускать примерно такой скрипт:
```sh
#!/bin/sh

# кладем в inbound написанные пользователями письма:
/home/fidonet/bin/sql2pkt.pl
# тоссим всё, что появилось в inbound (включая входящую почту):
/home/fidonet/bin/hpt toss
# засасываем в базу появившиеся xml:
/home/fidonet/bin/xml2sql.pl
# линкуем 
/var/www/wfido/bin/fastlink.php
```

Эти команды можно оформить в скрипт toss.sh и прописать его запуск в своём мейлере. Например, для binkd это будет выглядеть примерно так:
```
exec "/home/fidonet/bin/toss.sh" *.*
``` 

6. Проблемы.
6.1. При запуске sql2pkt.pl может ругаться примерно так:

```
Undefined subroutine &Exporter::export_ok_tags called at /usr/local/share/perl/5.10.0/FTN/Pkt.pm line 19.
BEGIN failed--compilation aborted at /usr/local/share/perl/5.10.0/FTN/Pkt.pm line 21.
Compilation failed in require at ./sql2pkt.pl line 2.
BEGIN failed--compilation aborted at ./sql2pkt.pl line 2.
```

В этом случае надо открыть файл /usr/local/share/perl/5.10.0/FTN/Pkt.pm
и в 12ой строке заменить "require" на "use";

6.2. При запуске xml2sql.pl может ругаться так:
```
Can't locate Digest/Perl/MD5.pm in @INC (@INC contains: /usr/local/lib/perl5/5.12.4/BSDPAN /usr/local/lib/perl5/site_perl/5.12.4/mach /usr/local/lib/perl5/site_perl/5.12.4 /usr/local/lib/perl5/5.12.4/mach /usr/local/lib/perl5/5.12.4 .) at xml2sql.pl line 4.
BEGIN failed--compilation aborted at xml2sql.pl line 4.
```

В этом случае надо заменить в файле xml2sql.pl строку
```
use Digest::Perl::MD5 'md5_hex';
```
на
```
use Digest::MD5 'md5_hex';
```
