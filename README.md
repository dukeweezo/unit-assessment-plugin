## Unit plugin

### Installation
(Optional: run locally
  by installing / running [MAMP](https://codex.wordpress.org/Installing_WordPress_Locally_on_Your_Mac_With_MAMP), then
  install Wordpress.
)


1. Place the files into a directory @ `'../wp-content/plugins/assessment'`.

2. Add the API key and API url to your `wp-config.php` file like so:
```
define( 'UNIT_URL', $the_url );
define( 'UNIT_API_KEY', $the_key );
```
(Refer to [this doc](https://docs.google.com/document/d/1wrr4Eu0S9OkeO8Lq0nKVcvDS5cJmHCwKp6apgDwjNjc/edit#) for the actual values).

### Usage

1. Activate the plugin.
2. Generate units via `Assessment Menu` in admin. (Currently limited to 100 units. UI currently statically generated in PHP; ideally it would be rendered asynchronously with JavaScript).

![alt text](https://github.com/dukeweezo/unit-assessment-plugin/blob/main/tut-imgs/1.png?raw=true)

* These are populated under 'Units'.

![alt text](https://github.com/dukeweezo/unit-assessment-plugin/blob/main/tut-imgs/2.png?raw=true)

3. Use [units] shortcode in posts / pages.

![alt text](https://github.com/dukeweezo/unit-assessment-plugin/blob/main/tut-imgs/3.png?raw=true)

* These get displayed in two sections: a. with area equal to one.

![alt text](https://github.com/dukeweezo/unit-assessment-plugin/blob/main/tut-imgs/4.png?raw=true)

* b. with area greater than one.

![alt text](https://github.com/dukeweezo/unit-assessment-plugin/blob/main/tut-imgs/5.png?raw=true)
