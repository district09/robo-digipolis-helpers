# Robo Digipolis Helpers

[![Latest Stable Version](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/v/stable)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![Latest Unstable Version](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/v/unstable)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![Total Downloads](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/downloads)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)
[![License](https://poser.pugx.org/digipolisgent/robo-digipolis-helpers/license)](https://packagist.org/packages/digipolisgent/robo-digipolis-helpers)

[![Build Status](https://travis-ci.org/digipolisgent/robo-digipolis-helpers.svg?branch=develop)](https://travis-ci.org/digipolisgent/robo-digipolis-helpers)
[![Maintainability](https://api.codeclimate.com/v1/badges/1c4c5693cb7945f5e5e9/maintainability)](https://codeclimate.com/github/digipolisgent/robo-digipolis-helpers/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1c4c5693cb7945f5e5e9/test_coverage)](https://codeclimate.com/github/digipolisgent/robo-digipolis-helpers/test_coverage)
[![PHP 7 ready](https://php7ready.timesplinter.ch/digipolisgent/robo-digipolis-helpers/develop/badge.svg)](https://travis-ci.org/digipolisgent/robo-digipolis-helpers)


Used by digipolis, abstract robo file to help with the deploy flow.


By default, we assume a [capistrano-like directory structure](http://capistranorb.com/documentation/getting-started/structure/):
```
├── current -> releases/20150120114500/
├── releases
│   ├── 20150080072500
│   ├── 20150090083000
│   ├── 20150100093500
│   ├── 20150110104000
│   └── 20150120114500
```
