# DemirPHP Veritabanı Sınıfı
Veritabanı işlemleri yapmayı kolaylaştıran kullanışlı bir sınıftır.

## Kurulum
Dosyaları indrirerek veya composer aracılığıyla kurulum yapılabilir
```
composer require demirphp/database
```

## Kullanımı
DemirPHP veritabanı sınıfı, SQL kodlarını, PHP metodlarıyla zincirleyerek yaratmanıza ve bunları isterseniz çalıştırmanızı sağlar.


* `SELECT` ifadesi
* `FROM` ifadesi ve tablo seçme
* Veri döndürme
* Sorgu dizgesi döndürme
* Hızlı veri döndürme
* Parametre ekleme
* Sorgu dizgesi elde etme
* `WHERE` ifadeleri
* `JOIN` ifadeleri
* `ORDER BY` ifadesi
* `GROUP BY` ifadesi
* `HAVING` ifadeleri
* `LIMIT` ifadesi
* `INSERT INTO` ifadeleri
* `UPDATE` ifadesi
* `DELETE FROM` ifadeleri

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
`find()` ve `findAll()`methodları 
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

// Tüm bunlar $db->table() dışında, $db->selectFrom('table') ve $db->select()->from('table') ile de kullanılabilir.
```

Devamı hazırlanacak...
