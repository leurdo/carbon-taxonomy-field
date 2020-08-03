# Taxonomy Carbon Field

Carbon Fields extension, adding "taxonomy" field type. It is select with ajax load and creation of new term.

### Requirements

* [Carbon Fields 3](https://github.com/htmlburger/carbon-fields)


## Installation

Add the following to composer.json:

```
"repositories": [
   {
      "type": "vcs",
      "url": "https://github.com/leurdo/carbon-taxonomy-field.git"
   }
],
"require": {
  "php": ">=5.3.2",
  "htmlburger/carbon-fields": "^3.0.0",
  "leurdo/carbon-field-taxonomy": "dev-master",
  "composer/installers": "^1.3.0"
}
```

## Usage

```
Field::make( 'taxonomy', 'field_name', 'Field Label' )
   ->set_tax( 'mytax' )
```


