# WordPress Quick Quiz

Answers are checked using the Levenshtein Distance string metric. This means that 
users are given a bit of leeway when entering their answers, should they make typos 
or not format their answer as was expected. The distance can be set on a per-quiz 
basis. Once a user finishes or forfeits a quiz, they are presented with the option 
to share their score and link to the quiz on various social networks. 

This plugin makes use of [FlipClock.js](http://flipclockjs.com/) for the timer 
effect and is easy to style to your liking with CSS.

The implementation of the Levenshtein Distance javascript function comes from 
[here](https://gist.github.com/andrei-m/982927).