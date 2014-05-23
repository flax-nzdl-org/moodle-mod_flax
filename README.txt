Moodle module for Flexible Language Acquisition System (FLAX)
Copyright (C) 2003 New Zealand FLAX Project, University Of Waikato
FLAX comes with ABSOLUTELY NO WARRANTY; for details see LICENSE.txt
This is free software, and you are welcome to redistribute it

--------------------------------------------------------
Change Log
--------------------------------------------------------

UPDATE 23/05/2014
- Revamp of graded exercise sendback; previously it was likely that graded data would not get successfully saved on the moodle server due to configuration of module data. (e.g. Moodle servers stored on local machines would report their address as "localhost" to the flax server, which obviously isn't going to work if the server tries to make a new request to the moodle server and it's not on the same domain).
- Clients now send grade data directly to the moodle server without going through the flax server. This was slightly awkward and required a same-origin policy workaround (using the cross-document messaging API)
- See the comments at the top of design_module.js for more details


UPDATE 24/04/2014
- Numerous updates to module to introduce new activities + fix old broken ones.
- Update to report functionality and grading:
  - Grading interface modified to be more helpful.
  - For graded exercises, they should be graded much more accurately over the number of marks given per exercise rather than number of questions in total. The gradebook summary has also been updated and fixed to be more accurate.
  - Numerous exercise content summaries now reflect this.
Below is a list of activites that have been tested and should have full functionality (including grading) working correctly:
- Hangman
- Scrambled Sentences
- Word Guessing
- Scrambled Paragraphs
- Split Sentences
- Collocation Matching
- Collocation Dominoes
- Related Words
- Collocation Guessing
Any activities not included on this list may not work correctly. They will one day be investigated further.

---------------------------------------------------------
Installation
---------------------------------------------------------
Tested on Moodle 2.5.x. & 2.6.x

---------------------------------------------------------
Configuration
---------------------------------------------------------
The module has a default external FLAX server at flax.nzdl.org. If you have installed your own FLAX server, please configure it to the appropriate server name and port number.
