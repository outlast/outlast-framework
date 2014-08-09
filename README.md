Outlast Framework
=================

The open-source Outlast Framework combines client-side javascript with a model-view-controller server-side PHP interface that is a joy to use. It’s design allows a simple workflow for front- and backend development, turning chaotic code into a standardized work of art.

Visit [Outlast Framework's](http://framework.outlast.hu/) website to learn more about getting started.

So what makes Outlast Framework special?
----------------------------------------
 - client- and server-side features abound: ajax, pushstate, and other HTML5 goodies are fun to use
 - modular design allows you to create (and/or use) plugins, extend, customize, and reuse them
 - a django-inspired template system with powerful template inheritance features
 - super-nice model and db api: never worry about multi-table joins or SQL syntax – unless of course you want to
 - connects to WordPress (via plugin), so blogging and CMS is built-in
 - heavily standardized, so once you know it, it’s predictable and logical, no matter who wrote the code
 - developed and supported by us (Outlast) while also being free and open-source

Some notes about cloning Outlast Framework
-------------------------------------------
We have no officially stable release yet, though the actively developed edition is used in several production sites and is considered ready for a production environment. Only tested changes considered stable enough are typically pushed to the Github.

Please note that the *system* folder in the master branch is handled as a submodule. Only specific releases will have the actual files in the repo as well. So, when you clone the repo, you'll also need to init submodules before you can start using OFW:

	git pull
	git submodule init
    git submodule update --recursive

To update the *system* folder to the latest development version at any time, simply pull and update.

	git pull
    git submodule update --recursive

Visit http://framework.outlast.hu/ for documentation and more information.

If you cannot clone the system folder
-------------------------------------
If you cannot access the *system* folder's repository (which is a public repo on our Stash server for development versions), you can try [downloading the Stash master branch version](https://develop.outlast.hu/stash/plugins/servlet/archive/projects/OFW/repos/outlast-framework-system?at=refs%2Fheads%2Fmaster) or cloning it from its [Github repo](https://github.com/outlast/outlast-framework-system).

You can then replace the existing *system* folder with the one you downloaded / cloned.