/*
See http://webplayer.yahoo.com/docs/how-to-set-up/#customize
This file of customizations must come before  <script type="text/javascript" src="http://webplayer.yahooapis.com/player.js">
*/

/*
stop the player from advancing to the next track in the playlist.  
We do not want participants getting another sound track automatically; this could be confusing.

Also include a default gif in the player which overrides the play.gif, so as to avoid confusion.  We don't want users clicking a gif that does nothing.
Note that defaultalbumart does not work when an image is already in the anchor tag.  So I didn't bother setting it.  However, I put the image I want as
the first image tag in anchor, like so
Regular rhythm <a href="audio/Regular_Normal_Heart_Beat.mp3"><img src="images/spacer.gif" style="display:none" /><img src="images/play_gray.png" /></a>

Make the volume max loud by default
*/
var YWPParams = 
{
    autoadvance: false,
    volume: 1.0
};