/*==================================================
  $Id: tabber.js,v 1.3 2006/03/28 16:57:42 rsk Exp $
  tabber.js by Patrick Fitzgerald pat@barelyfitz.com
  http://www.barelyfitz.com/projects/tabber/

  License (http://www.opensource.org/licenses/mit-license.php)

  Copyright (c) 2006 Patrick Fitzgerald

  Permission is hereby granted, free of charge, to any person
  obtaining a copy of this software and associated documentation files
  (the "Software"), to deal in the Software without restriction,
  including without limitation the rights to use, copy, modify, merge,
  publish, distribute, sublicense, and/or sell copies of the Software,
  and to permit persons to whom the Software is furnished to do so,
  subject to the following conditions:

  The above copyright notice and this permission notice shall be
  included in all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
  BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
  ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.
  ==================================================

  This file contains three functions:

    tabberObj()
    Constructor for a tabber object.

    tabberAutomatic()
    Convenience function: searches the document for elements
    to convert to tabber interfaces.

    tabberAutomaticOnLoad()
    Convenience function: adds an onload event to the document
    to call tabberAutomatic.

  NOTE: when you include this file, it automatically calls
  tabberAutomaticOnLoad() unless you define the tabberNoOnLoad global
  variable before including this file.

  Refer to the URL listed at the top of this file for more
  documentation.

  ==================================================*/

function tabberObj()
{
  /* This object converts the contents of a div element
     into a dynamic tabbed interface.

     Example 1, simple usage using default properties:

       var mytab = new tabberObj({div:document.getElementById('mydiv')});

     Example 2, override the defaults for the classMain and
     classMainLive properties:

       var mytab = new tabberObj({
         div:document.getElementById('mydiv'),
         classMain:'mytabber',
         classMainLive:'mytabberlive'
       });
  */

  /* Element for the main tabber div */
  /* this.div */

  /* Class of the main tabber div */
  this.classMain = "tabber";

  /* Rename classMain to classMainLive after tabifying
     (so a different style can be applied)
  */
  this.classMainLive = "tabberlive";

  /* Class of each DIV that contains a tab */
  this.classTab = "tabbertab";

  /* Class to indicate which tab should be active on startup */
  this.classTabDefault = "tabbertabdefault";

  /* Class for the navigation UL */
  this.classNav = "tabbernav";

  /* When a tab is to be hidden, instead of setting display='none', we
     set the class of the div to classTabHide. In your screen
     stylesheet you should set classTabHide to display:none.  In your
     print stylesheet you should set display:block to ensure that all
     the information is printed.
  */
  this.classTabHide = "tabbertabhide";

  /* Class to set the navigation LI when the tab is active, so you can
     use a different style on the active tab.
  */
  this.classNavActive = "tabberactive";

  /* Array of objects holding info about each tab */
  this.tabs = new Array();

  /* Override the defaults by passing in an object containing overrrides:
     var mytab = new tabber({property:value,property:value})
  */
  for (var n in arguments[0]) { this[n] = arguments[0][n]; }

  /* If the main tabber div was specified, call init() now */
  if (this.div) { this.init(this.div); }
}


/*--------------------------------------------------
  Methods for tabberObj
  --------------------------------------------------*/


tabberObj.prototype.init = function(e)
{
  /* Set up the tabber interface.

     e = element (the main containing div)

     Example:
     init(document.getElementById('mytabberdiv'))
   */

  var i, defaultTab=0;

  /* Store the main containing div.
     I don't need it but someone might find a use for it.
  */
  this.div = e;

  /* Attach this object to the tabber div so anyone can reference it. */
  e.tabber = this;

  /* Verify that the browser supports DOM scripting */
  if (!document.getElementsByTagName) { return false; }

  /* Clear the existing tabs array (shouldn't be necessary) */
  this.tabs.length = 0;

  /* Create some regular expressions to match class names */
  var reMain = new RegExp('\\b' + this.classMain + '\\b', 'i');
  var reTab = new RegExp('\\b' + this.classTab + '\\b', 'i');
  var reDefault = new RegExp('\\b' + this.classTabDefault + '\\b', 'i');

  /* Loop through an array of all the child nodes within our tabber element. */
  var childNodes = e.childNodes;
  for(i=0; i < childNodes.length; i++) {

    /* Find the nodes where class="tabbertab" */
    if(childNodes[i].className && childNodes[i].className.match(reTab)) {
      
      /* Create a new object to save info about this tab */
      t = new Object();
      
      /* Save a pointer to the div for this tab */
      t.div = childNodes[i];
      
      /* Add the new object to the array of tabs */
      var tabIndex = this.tabs.length;
      this.tabs[tabIndex] = t;

      /* Should this tab be selected by default */
      if (childNodes[i].className.match(reDefault)) {
	defaultTab = tabIndex;
      }
    }
  }

  /* Create a new UL list to hold the tab headings */
  var ul = document.createElement("ul");
  ul.className = this.classNav;
  
  /* Loop through each tab we found */
  for (i=0; i < this.tabs.length; i++) {

    var t = this.tabs[i];

    /* Get the heading from the title attribute on the DIV,
       or just use an automatically generated number.
    */
    t.headingText = t.div.title;
    if (!t.headingText) {
      t.headingText = i + 1;
    }

    /* Create a link to activate the tab */
    t.a = document.createElement("a");
    t.a.appendChild(document.createTextNode(""));
    t.a.firstChild.data = t.headingText;
    t.a.href = "javascript:void(null);";
    t.a.title = t.headingText;
    t.a.onclick = this.onClick;

    /* Add some properties to the link so we can identify which tab
       was clicked. Later the onClick method will need this.
    */
    t.a.tabber = this;
    t.a.tabbertabid = i;

    /* Create a list element for the tab */
    t.li = document.createElement("li");

    /* Add the link to the list element */
    t.li.appendChild(t.a);

    /* Add the list element to the list */
    ul.appendChild(t.li);
  }

  /* Add the UL list to the tabber */
  e.insertBefore(ul, e.firstChild);

  /* Make the tabber div "active" so different CSS can be applied */
  e.className = e.className.replace(reMain, this.classMainLive);

  /* Activate the default tab */
  this.tabShow(defaultTab);

  return this;
}


tabberObj.prototype.onClick = function()
{
  /* This method should only be called by the onClick event of an <A>
     element, in which case we will determine which tab was clicked by
     examining a property that we previously attached to the <A>
     element.

     Since this was triggered from an onClick event, the variable
     "this" refers to the <A> element that triggered the onClick
     event (and not to the tabberObj).

     When tabberObj was initialized, we added some extra properties
     to the <A> element, for the purpose of retrieving them now. Get
     the tabberObj object, plus the tab number that was clicked.
  */
  var a = this;
  if (!a.tabber) { return false; }

  var self = a.tabber;
  var tabindex = a.tabbertabid;

  /* Remove focus from the link because it looks ugly.
     I don't know if this is a good idea...
  */
  a.blur();

  self.tabShow(tabindex);

  return false;
}


tabberObj.prototype.tabHideAll = function()
{
  /* Hide all tabs and make all navigation links inactive */
  for (var i = 0; i < this.tabs.length; i++) {
    this.tabHide(i);
  }
}


tabberObj.prototype.tabHide = function(tabindex)
{
  /* Hide the tab and make its navigation link inactive */
  var div = this.tabs[tabindex].div;

  /* Hide the tab contents by adding classTabHide to the div */
  var re = new RegExp('\\b' + this.classTabHide + '\\b');
  if (!div.className.match(re)) {
    div.className += ' ' + this.classTabHide;
  }
  this.navClearActive(tabindex);
}


tabberObj.prototype.tabShow = function(tabindex)
{
  /* Note: this method enforces the rule that only one tab at a time
     should be active.
  */

  if (!this.tabs[tabindex]) { return false; };

  /* Hide all the tabs first */
  this.tabHideAll();

  /* Get the div that holds this tab */
  var div = this.tabs[tabindex].div;

  /* Remove classTabHide from the div */
  var re = new RegExp('\\b' + this.classTabHide + '\\b', 'g');
  div.className = div.className.replace(re, '');

  /* Mark this tab navigation link as "active" */
  this.navSetActive(tabindex);

  return this;
}


tabberObj.prototype.navSetActive = function(tabindex)
{
  /* Note: this method does *not* enforce the rule
     that only one nav item can be active at a time.
  */

  /* Set classNavActive for the navigation list item */
  this.tabs[tabindex].li.className = this.classNavActive;

  return this;
}


tabberObj.prototype.navClearActive = function(tabindex)
{
  /* Note: this method does *not* enforce the rule
     that one nav should always be active.
  */

  /* Remove classNavActive from the navigation list item */
  this.tabs[tabindex].li.className = '';

  return this;
}


/*==================================================*/


function tabberAutomatic(tabberArgs)
{
  /* This function finds all DIV elements in the document where
     class=tabber.classMain, then converts them to use the tabber
     interface.

     tabberArgs = an object to send to "new tabber()"
  */

  if (!tabberArgs) { tabberArgs = {}; }

  /* Create a tabber object so we can get the value of classMain */
  var tempObj = new tabberObj(tabberArgs);

  /* Regular expression to find the matching className */
  var reMain = new RegExp('\\b' + tempObj.classMain + '\\b', 'i');

  /* Find all DIV elements in the document that have class=tabber */

  /* First get an array of all DIV elements and loop through them */
  var divs = document.getElementsByTagName("div");
  for (var i=0; i < divs.length; i++) {
    
    /* Is this DIV the correct class? */
    if (divs[i].className &&
	divs[i].className.match(reMain)) {
      
      /* Now tabify it */
      tabberArgs.div = divs[i];
      tempObj = new tabberObj(tabberArgs);
    }
  }
  
  return this;
}


/*==================================================*/


function tabberAutomaticOnLoad(tabberArgs)
{
  /* This function adds tabberAutomatic to the window.onload event,
     so it will run after the document has finished loading.
  */

  if (!tabberArgs) { tabberArgs = {}; }

  /* Taken from: http://simon.incutio.com/archive/2004/05/26/addLoadEvent */

  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = function() {
      tabberAutomatic(tabberArgs);
    }
  } else {
    window.onload = function() {
      oldonload();
      tabberAutomatic(tabberArgs);
    }
  }
}


/*==================================================*/


/* Run tabberAutomaticOnload() unless the tabberManual global variable has been set */
if (typeof tabberNoOnLoad == 'undefined' || !tabberNoOnLoad) {
  tabberAutomaticOnLoad();
}
