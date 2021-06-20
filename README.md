Outlast Framework
=================

Outlast Framework is an MVC-based PHP framework built specifically for dev shops and agencies.

Visit [Outlast Framework's](http://framework.outlast.io/) website to learn more about getting started.

Warning: this 21.1 release is the final release of Outlast Framework - support has ended as of January 2021. For new projects, consider using [Laravel](https://laravel.com).

So what makes Outlast Framework special?
----------------------------------------

Developing and supporting dozens or even hundreds of projects? Outlast Framework aims to standardize and modularize this process at a level that goes beyond anything you’ve seen in a PHP framework…

It has all the usual goodies you expect (open source, MVC design, powerful templating engine, ORM, built-in unit-testing, etc.) but adds a convention-over-configuration attitude, and a unique approach to modularity.

Grab a copy of Outlast Framework
--------------------------------

Outlast Framework is actively developed and used on several production sites. It is considered ready for a production environment. Only tested changes considered stable enough are typically pushed as Github releases.

That said, there are still some incomplete features in OFW, so if you have requests or run into trouble [let us know](http://framework.outlast.io/support/).  

Releases are published every few months and [can be grabbed here](https://github.com/outlast/outlast-framework/releases).


Some notes about cloning Outlast Framework
------------------------------------------
If you want to actively develop features for Outlast Framework, you'll need to clone a copy or add the *system* submodule and submit pull requests for consideration.

Please note that the *system* folder in the master branch is handled as a submodule. So, when you clone the repo, you'll also need to init submodules before you can start using OFW:

	git pull
    git submodule update --recursive --init

You can also repeat the command to update to the latest version at any time.

Visit http://framework.outlast.io/ for documentation and more information.