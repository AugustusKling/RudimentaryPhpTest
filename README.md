# RudimentaryPhpTest is a very simple framework for running PHP unit tests.

Tests can be stored in any nested folder or a single file. The framework traverses the folder to find PHP files. If those define classes inheriting from `RudimentaryPhpTest_BaseTest` they are seen as test classes. All methods matching a filter expression are executed as tests – the default is to execute all methods whose name ends in `Test`.

After the test runs complete a simple summary table is printed to the console which lists the tests and their assertions.

### Requirements
So far, this framework was developed and tested on PHP 5.2 and PHP 5.3 running on Linux. It should run on any PHP 5 in theory, though.

There are no other dependencies.

### Usage
Get the framework by simply cloning the repository. An installation or setup is not required.

Invocation is as follows where the parameter `testfilter` is optional and `testbase` gives the root file or directory with tests to execute. Optionally, a file for preparing the environment for testing can be given with the `bootstrap` parameter:

    php RudimentaryPhpTest.php --testbase='samples' [ --testfilter='Test$' ] [ --bootstrap='….php' ]

All test classes within the directory given by `testbase` will be executed and test progress followed by a summary table will be printed.

Exit codes are as follows:

* 0: All tests succeeded (see console output for summary)
* 1: At least one assertion failed (see console output for exceptions and summary)
* 255: PHP crashed / Test execution was aborted (see console output for some information)

### Documentation
See the [wiki](/AugustusKling/RudimentaryPhpTest/wiki) and especially the [simple tutorial](/AugustusKling/RudimentaryPhpTest/wiki/Tutorials-Simple) to get started. Further sample code is available from [the samples directory](/AugustusKling/RudimentaryPhpTest/tree/master/samples).

Setup of bootstrapping and logging for integration with continuous integration systems can also be found in the wiki.