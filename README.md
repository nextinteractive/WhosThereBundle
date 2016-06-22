WhosThereBundle
===============

[![Build Status](https://travis-ci.org/Lp-digital/WhosThereBundle.svg?branch=master)](https://travis-ci.org/Lp-digital/WhosThereBundle)
[![Code Climate](https://codeclimate.com/github/Lp-digital/WhosThereBundle/badges/gpa.svg)](https://codeclimate.com/github/Lp-digital/WhosThereBundle)
[![Test Coverage](https://codeclimate.com/github/Lp-digital/WhosThereBundle/badges/coverage.svg)](https://codeclimate.com/github/Lp-digital/WhosThereBundle/coverage)

**WhosThereBundle** notifies contributors that the page they are currently working on has some revisions yet from other ones.

Installation
---------------

Edit the file `composer.json` of your BackBee project.

Add the new dependency to the bundle in the `require` section:
```
# composer.json
...
    "require": {
        ...
        "lp-digital/whosthere-bundle": "*"
    },
...
```

Save and close the file.

Run a composer update on your project.


Activation
--------------

Edit the file `repository/Config/bundles.yml`of your BackBee project.

Add the following line at the end of the file:
```
# bundles configuration - repository/Config/bundles.yml
...
whosthere: LpDigital\Bundle\WhosThereBundle\WhosThere
```

Save and close the file.

Depending on your configuration, cache may need to be clear.

---

*This project is supported by [Lp digital](http://www.lp-digital.fr/en/)*

**Lead Developer** : [@crouillon](https://github.com/crouillon)

Released under the GPL3 License
