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

$pdo = new PDO('mysql:host=localhost;dbname=database;charset=utf8', 'user', 'pass');
$db = Database::init($pdo);

$query = Database::select()
	->from('post')
	->where('id=5')
	->build();

// $query içeriği: SELECT * FROM post WHERE id = 5
// ya da şöyle de kullanabiliriz:

$query = Database::select('id, title, body, created_at, category_id, draft')
	->from('post')
	->join('category ON category.id=post.category_id')
	->where('draft=0')
	->orderBy('created_at DESC')
	->build(); // veya ->fetchAll(); (veri döndürür)

// $query içeriği: SELECT id, title, body, created_at, category_id, draft FROM post INNER JOIN category ON category.id = post.category_id WHERE draft = 0 ORDER BY created_at DESC
```

## `SELECT` ifadesi
`SELECT` ifadesi tablodaki sütunları seçmeye yardımcı bir metodlardır.

```php
Database::select()->build();
// "SELECT *" döner
Database::select('id, title, body AS govde')->build();
// "SELECT id, title, body AS govde" döner.

// Daha hızlı biçimde, şöyle de kullanılabilir:
Database::table('post')->build();
// "SELECT * FROM post" döner
```

## `FROM` ifadesi ve tablo seçme
Tablo seçmeye yardımcı olan metodlardır, tanıyalım:

```php
Database::select()->from('table')->build();
// "SELECT * FROM table" döner

// veya, şöyle de kullanılabilir:
Database::table('table')->build();
// "SELECT * FROM table" döner
```

## Veri Döndürme
Veri listelemek ve çekmek istediğimizde bize yardımcı olan metodlardır.
`fetch()` `fetchAll()` `fetchColumn()` `rowCount()`

```php
Database::select()
	->from('post')
	->where('id=?')
	->param([$id])
	->execute()
	->fetch();
// "SELECT * FROM post WHERE id = ?" sorgusunu parametreyle birlikte çalıştıracaktır

Database::select()
	->from('post')
	->where('draft=0')
	->where('AND approved="yes"')
	->execute()
	->fetchAll();
// Birden fazla satır döndürecektir

Database::select('COUNT(id)')
	->from('post')
	->execute()
	->fetchColumn();
// Satır sayısını döndürecektir
// ya da
Database::table('post', 'COUNT(id)')
	->execute()
	->fetchColumn();
// Veya şu şekilde de kullanılabilir:
Database::table('post')->execute()->rowCount();
// İlk kullanılan rowCount'a göre daha hızlıdır
```
veri döndürmenin daha birçok farklı yolu mevcut. Şöyle mesela;
```php
Database::query('SELECT * FROM table')
	->fetchAll();
// veya
Database::table('table')
	->execute()
	->fetchAll();
```
`query()` metodu, PDO nesnesi döndürdüğünden, başka biçimlerde de kullanabilirsiniz. 

## Sorgu Dizgesi Döndürme
`build()` methodu ile sorgu dizgesi oluşturabiliyoruz
```php
Database::select()
	->from('table')
	->build();
// "SELECT * FROM table" dizgesi döner
```

## Hızlı Veri Döndürme
`find()`  ve  `findAll()`methodları 
```php
Database::table('post')->find(15);
// ID'si 15 olan satırı döndürür
// Primary Key'i 'id' olarak alır, eğer farklıysa şöyle kullanılabilir:
Database::table('post')->find(15, 'postID');

// Birden fazla veri döndürmek için şunu kullanabiliriz
Database::table('post')->findAll();
// Tablodaki bütün verileri döndürecektir
// İstersek şart ekleyebiliriz:
Database::table('post')->findAll('approved', 'yes');
// "SELECT * FROM post WHERE approved = 'yes'" sorgusunu çalıştırıp veri döndürecektir

// Birden fazla şart için şöyle kullanılabilir
Database::table('post')
	->findAll([
		'draft' => 0,
		'approved' => 'yes'
	]);

// Tüm bunlar Database::table() dışında Database::select()->from('table') ile de kullanılabilir.
```

## Parametre Ekleme
Parametreler, bir sorgu içerisindeki dizgeyi, belirtilen değişkenle ilişkilendirmeye yarar. PDO'nun bir özelliğidir. Bu özellik, veri döndürmek istediğimizde kendini dayatır.

```php
Database::table('table')
	->where('id=:id')
	->param(':id', 5)
	// veya ->param(['id' => 5])
	->execute()
	->fetch();
```
Parametreler birden fazla `bindParam` metodu çalıştırılarak eklenebilir, önceki eklenenlerin üzerine eklenmez.

## `WHERE` İfadeleri
`WHERE` ifadesi ile koşullu sorgular oluşturabiliyoruz.

`where()` `orWhere()` `andWhere()` 
`whereIn()` `orWhereIn()` `andWhereIn()`
`whereBetween()` `orWhereBetween()` `andWhereBetween()`

```php
Database::table('table')
	->where('type="post"')
	->where('AND approved="yes"')
	->build();
// "SELECT * FROM table WHERE type = 'post' AND approved = 'yes'" ifadesini döndürür

Database::table('table')
	->where('draft=1')
	->where('OR type="page"')
	->build();
// .. WHERE .. OR .. ifadesi döndürür

Database::table('table')
	->where('id ' . DB::in([1, 2, 3, 4]))
	->build();
// "SELECT * FROM table WHERE id IN (1, 2, 3, 4)" ifadesi döner, parametre kullanılabilir

Database::table('post')
	->where('created ' . DB::between('2016-07-15', '2016-07-18'))
	->build();
// "SELECT * FROM post WHERE created BETWEEN 2016-07-15 AND 2016-07-18" ifadesi döner, parametre kullanılabilir
```

## `JOIN` İfadeleri
Bir sorguda iki tablodan veri elde etmek için kullanılır.
`join()` `innerJoin()` `leftJoin()` `rightJoin()` `fullJoin()`

```php
Database::select('post.*, category.name AS cat_name, category.id AS cat_id')
	->from('post')
	->join('category NON post.category_id=category.id')
	->build();

// "SELECT post.*, category.name AS cat_name, category.id AS cat_id FROM post INNER JOIN category ON post.category_id = category.id" döndürür
// veya
Database::select()
	->from('post AS p')
	->rightJoin('category AS c ON c.id=p.cid')
	->build();
```

`join()` methodu `innerJoin()` methoduna denktir.

## `ORDER BY` İfadesi
Sıralamayı ayarlar

```php
Database::table('table')->orderBy('created DESC')->build();
// SELECT * FROM table ORDER BY created DESC
// ya da
Database::table('table')->orderBy('id ASC, created DESC')->build();
```

## `GROUP BY` İfadesi
```php
Database::table('table')
	->join(...)
	->groupBy('table.groupID')
	->build();
```

## `HAVING` İfadeleri
`having()` `orHaving()` `andHaving()` 
```php
Database::table('post')
	->having('approved="yes"')
	->having('AND ...')
	->having('OR ...')
	->build();
```

## `LIMIT` İfadesi
```php
Database::table('post')
	->limit(10)
	->build();
// veya
Database::table('post')
	->limit('10,20')
	->build();
// "SELECT * FROM post LIMIT 10,20" döndürür
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

Database::table('post')
	->insert($post)
	->execute();
// execute() methodu hazırlanan veriyi ekler
```

Sorguyu çalıştırdıktan sonra, son eklenen verinin ID'sini şöyle alabiliriz

```php
$id = DB::pdo()->lastInsertId();
```

## `UPDATE` İfadesi
```php
$post = [
	'title' => 'Başlık',
	'body' => 'İçerik',
	'created' => '2016-05-01'
];

Database::table('post')
	->update($post)
	->where('id=5')
	->execute();
```

## `DELETE FROM` İfadesi
```php
Database::table('post')
	->delete()
	->where('id=5')
	->execute();
// veya
Database::table('post')
	->delete('id=5')
	->execute();
// veya
Database::table('post')
	->delete('id=:id')
	->param(':id', 5)
	->execute();
```

## Diğer Metodlar
Kullanabileceğimiz diğer metodlar
### `pdo()` metodu
Bu metod PDO nesnesini döndürür.  Örneğin:
```php
Database::table(...)->insert(...)->execute();
Database::pdo()->lastInsertId(); // Son eklenen ID 
```
### `query()` metodu
SQL sorgusu çalıştırmaya yarar, ikinci ve diğer değerler parametreleri ifade eder.
```php
Database::query('SELECT * FROM post WHERE id=? AND draft=?', $id, 'published')->fetchAll();
// ya da
Database::query('SELECT * FROM post WHERE id=:id AND draft=:draft', [
	':id' => 5,
	':draft' => 'published'
])->fetchAll();
```
### `notClear()` metodu
Normal şartlarda sınıf, içerisinde ürettiği SQL kodunu, bir sonraki SQL kodunda temizler. Bu metodu kullandığımızda ise, SQL kodu sınıf içerisinden silinmez ve tekrar kullanılabilir. Örnekleyim:
```php
Database::notClear();
$result = Database::table('post')->findAll();
Database::getQuery(); // SELECT * FROM post
Database::execute()->rowCount(); // Gönderi sayısı döner
```
### `getQuery()` metodu
Son çalıştırılan sorguyu döndürür.
```php
Database::table('post')->findAll();
Database::getQuery(); // SELECT * FROM post
```
### `getParams()` metodu
Son çalıştırılan sorguya ait parametreleri `notClear()`  metodu çalıştırılmışsa döndürür. Örnek:
```php
Database::notClear();
Database::table('post')->find(12);
Database::getParams(); // array ( ':id' => 12 )
```
