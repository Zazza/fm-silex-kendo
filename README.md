# fm-silex-kenju
File manager - Silex + Kendo UI

* doesn't use a database
* general operations: copy, move, remove, rename
* creation of image preview
* select files or folders with ctrl, shift
* list or icons view for folders and files
* sort by name, to the size, date
* creation of archive for download a set of files
* progress bar for uploading and downloading
* cool interface :) in the form of the window app

### PHP Tests
```sh
$ cd app/tests
$ phpunit
```

### Version
Development (more tests and feedback needed)

### Tech
* [Kenju](https://github.com/Zazza/Kenju) - fork Kendo UI framework from Telerik
* [Scrollbar](https://github.com/malihu/malihu-custom-scrollbar-plugin) - malihu custom scrollbar plugin
* [Jquery](http://jquery.com)
* [JavaScript Cookie](https://github.com/js-cookie/js-cookie)
* [FileSaver.js](https://github.com/eligrey/FileSaver.js/) - An HTML5 saveAs() FileSaver implementation


## Install:
```sh
$ git clone https://github.com/Zazza/fm-silex-kendo.git
$ cd fm-silex-kenju/app/
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
$ cd ..
$ git clone https://github.com/Zazza/Kenju.git
$ mkdir upload
$ mkdir thumb
$ chown -R www-data:www-data upload 
$ chown -R www-data:www-data thumb 
$ chmod 770 upload thumb
```

## Demo
[See](http://8x86.ru/fm-silex-kenju)

### Todos
 - Dmitry Samotoy

### License
GPLv3
