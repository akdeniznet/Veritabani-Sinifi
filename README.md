# DemirPHP Veritabanı Sınıfı
Veritabanı işlemleri yapmayı kolaylaştıran kullanışlı bir sınıftır.

## Kurulum
Dosyaları indrirerek veya composer aracılığıyla kurulum yapılabilir
```
composer require demirphp/database
```

## Kullanımı
DemirPHP veritabanı sınıfı, SQL kodlarını, PHP metodlarıyla zincirleyerek yaratmanıza ve bunları isterseniz çalıştırmanızı sağlar.


* [`SELECT` ifadesi](#select-ifadesi)
* [`FROM` ifadesi ve tablo seçme](#from-ifadesi-ve-tablo-se%C3%A7me)
* [Veri döndürme](#veri-d%C3%B6nd%C3%BCrme)
* [Sorgu dizgesi döndürme](#sorgu-dizgesi-d%C3%B6nd%C3%BCrme)
* [Hızlı veri döndürme](#h%C4%B1zl%C4%B1-veri-d%C3%B6nd%C3%BCrme)
* [Parametre ekleme](#parametre-ekleme)
* [`WHERE` ifadeleri](#where-İfadeleri)
* [`JOIN` ifadeleri](#join-İfadeleri)
* [`ORDER BY` ifadesi](#order-by-İfadesi)
* [`GROUP BY` ifadesi](#group-by-İfadesi)
* [`HAVING` ifadeleri](#having-İfadeleri)
* [`LIMIT` ifadesi](#limit-İfadesi)
* [`INSERT INTO` ifadeleri](#insert-into-İfadeleri)
* [`UPDATE` ifadesi](#update-İfadesi)
* [`DELETE FROM` ifadeleri](#delete-from-İfadeleri)

**Hızlı Örnek**
```php
// Composer olmadan kullanımı
require 'src/DemirPHP/Database.php';
// Composer ile kurulduysa 
// require 'vendor/autoload.php';
use DemirPHP\Database;

$pdo = new PDO('mysql:host=localhost;dbname=database;', 'user', 'pass');
$db = new Database($pdo);

$query = $db->select()
  ->from('post')
  ->where('id', '=', 5)
  ->build();

// $query içeriği: SELECT * FROM post WHERE id = 5
// ya da şöyle de kullanabiliriz:

$query = $db->select('id', 'title', 'body', 'created_at', 'category_id', 'draft')
  ->from('post')
  ->join('category', 'category.id', '=', 'post.category_id')
  ->where('draft', '=', 0)
  ->orderBy('created_at', 'DESC')
  ->build(); // veya ->fetchAll(); (veri döndürür)

// $query içeriği: SELECT id, title, body, created_at, category_id, draft FROM post INNER JOIN category ON category.id = post.category_id WHERE draft = 0 ORDER BY created_at DESC
```

## `SELECT` ifadesi
`SELECT` ifadesi tablodaki sütunları seçmeye yardımcı bir metodlardır.

```php
$db->select()->build();
// "SELECT *" döner
$db->select('id', 'title', 'body AS govde')->build();
// "SELECT id, title, body AS govde" döner.

// Daha hızlı biçimde, şöyle de kullanılabilir:
$db->selectFrom('table')->build();
// "SELECT * FROM table" döner
```

## `FROM` ifadesi ve tablo seçme
Tablo seçmeye yardımcı olan metodlardır, tanıyalım:

```php
$db->select()->from('table')->build();
// "SELECT * FROM table" döner

// veya, şöyle de kullanılabilir:
$db->selectFrom('table')->build();
// "SELECT * FROM table" döner

// Bu şekilde kullanımı da mümkündür:
$db->table('post')->build();
// "SELECT * FROM post" döner
```

## Veri Döndürme
Veri listelemek ve çekmek istediğimizde bize yardımcı olan metodlardır.
`fetch()` `fetchAll()` `fetchColumn()` `rowCount()`

```php
$db->select()
	->from('post')
	->where('id', '=', '?')
	->bindParam([$id])
	->fetch();
// "SELECT * FROM post WHERE id = ?" sorgusunu parametreyle birlikte çalıştıracaktır

$db->select()
	->from('post')
	->where('draft', '=', 0)
	->andWhere('approved', '=', 'yes')
	->fetchAll();
// Birden fazla satır döndürecektir

$db->select('count(id)')
	->from('post')
	->fetchColumn();
// Satır sayısını döndürecektir
// Veya şu şekilde de kullanılabilir:
$db->select()->from('post')->rowCount();
// İlk kullanılan rowCount'a göre daha hızlıdır
```

## Sorgu Dizgesi Döndürme
`build()` methodu ile sorgu dizgesi oluşturabiliyoruz
```php
$db->select()->from('table')->build();
// "SELECT * FROM table" dizgesi döner
```

## Hızlı Veri Döndürme
`find()`  ve  `findAll()`methodları 
```php
$db->table('post')->find(15);
// ID'si 15 olan satırı döndürür
// Primary Key'i 'id' olarak alır, eğer farklıysa şöyle kullanılabilir:
$db->table('post')->find(15, 'postID');

// Birden fazla veri döndürmek için şunu kullanabiliriz
$db->table('post')->findAll();
// Tablodaki bütün verileri döndürecektir
// İstersek şart ekleyebiliriz:
$db->table('post')->findAll('approved', 'yes');
// "SELECT * FROM post WHERE approved = 'yes'" sorgusunu çalıştırıp veri döndürecektir

// Birden fazla şart için şöyle kullanılabilir
$db->table('post')
	->findAll([
		'draft' => 0,
		'approved' => 'yes'
	]);

// Tüm bunlar $db->table() dışında, $db->selectFrom('table') ve $db->select()->from('table') ile de kullanılabilir.
```

## Parametre Ekleme
Parametreler, bir sorgu içerisindeki dizgeyi, belirtilen değişkenle ilişkilendirmeye yarar. PDO'nun bir özelliğidir. Bu özellik, veri döndürmek istediğimizde kendini dayatır.

```php
$db->selectFrom('table')
	->where('id', '=', ':id')
	->bindParam([':id' => 5])
	->fetch();
// veya ? işareti kullanabiliriz
```

## `WHERE` İfadeleri
`WHERE` ifadesi ile koşullu sorgular oluşturabiliyoruz.
`where()` `orWhere()` `andWhere()` `whereIn()`

```php
$db->selectFrom('table')
	->where('type', '=', 'post')
	->andWhere('approved', '=', 'yes')
	->build();
// "SELECT * FROM table WHERE type = 'post' AND approved = 'yes'" ifadesini döndürür

$db->selectFrom('table')
	->where('draft', '=', 1)
	->orWhere('type', '=', 'page')
	->build();
// .. WHERE .. OR .. ifadesi döndürür

$db->selectFrom('table')
	->whereIn('id', [1, 2, 3, 4])
	->build();
// "SELECT * FROM table WHERE id IN (1, 2, 3, 4)" ifadesi döner, parametre kullanılabilir
```

## `JOIN` İfadeleri
Bir sorguda iki tablodan veri elde etmek için kullanılır.
`join()` `innerJoin()` `leftJoin()` `rightJoin()` `fullJoin()`

```php
$db->select('post.*', 'category.name AS cat_name', 'category.id AS cat_id')
	->from('post')
	->join('category', 'post.category_id', '=', 'category.id')
	->rightJoin('')
	->build();

// "SELECT post.*, category.name AS cat_name, category.id AS cat_id FROM post INNER JOIN category ON post.category_id = category.id" döndürür
// veya
$db->select()
	->from('post AS p')
	->rightJoin('category AS c', 'c.id', '=', 'p.cid')
	->build();
```

`join()` methodu `innerJoin()` methoduna denktir.

## `ORDER BY` İfadesi
Sıralamayı ayarlar

```php
$db->selectFrom('table')->orderBy('created')->build();
// SELECT * FROM table ORDER BY created DESC
// ikinci parametre türü belirler
$db->selectFrom('table')->orderBy('id', 'ASC')->build();
```

## `GROUP BY` İfadesi
```php
$db->selectFrom('table')
	->join(...)
	->groupBy('table.groupID')
	->build();
```

## `HAVING` İfadeleri
`having()` `orHaving()` `andHaving()` 
```php
$db->selectFrom('post')
	->having('approved', '=', 'yes')
	->orHaving(...)
	->andHaving(...)
	->build();
```

## `LIMIT` İfadesi
```php
$db->selectFrom('post')
	->limit(10)
	->build();
// ikinci parametre OFFSET değeri alır
$db->selectFrom('post')
	->limit(10, 20)
	->build();
// "SELECT * FROM post LIMIT 10 OFFSET 20" döndürür
```

## `INSERT INTO` İfadeleri
Veri eklemek için iki yol var.
`insert()` ve `insertInto()` aynı işlevi yapar.

```php
$post = [
	'title' => 'Başlık',
	'body' => 'İçerik',
	'created' => '2016-05-01'
];

$db->insertInto('post', $post)->execute();
// veya $db->insert(...);
// execute() methodu hazırlanan veriyi ekler

$db->insert('post')
	->set('title', 'Başlık')
	->set('body', 'İçerik')
	->set('created', '2016-05-01')
	->execute();

// veya

$db->insert('post')->set($post)->execute();
```

Sorguyu çalıştırdıktan sonra, son eklenen verinin ID'sini şöyle alabiliriz

```php
$id = $db->lastInsertId();
```

## `UPDATE` İfadesi
```php
$post = [
	'title' => 'Başlık',
	'body' => 'İçerik',
	'created' => '2016-05-01'
];

$db->update('post')
	->set($post)
	->where('id', '=', 5)
	->execute();
// ya da
$db->update('post', $post)
	->where('postID', '=', 8)
	->execute();
```

## `DELETE FROM` İfadesi
```php
// $db->delete(...) veya
$db->deleteFrom('post')
	->where('id', '=', 5)
	->execute();
```
