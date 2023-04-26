/*
  moopop: unobtrusive javascript popups via late binding using mootools 1.2
  
  copyright (c) 2007-2008 by gonchuki - http://blog.gonchuki.com
  
  version:	1.1
  released: June 23, 2008
  
  This work is licensed under a Creative Commons Attribution-Share Alike 3.0 License.
    http://creativecommons.org/licenses/by-sa/3.0/
*/

/*
  Basic usage:
    add a rel attribute to your <a> tags to be like this:
      <a href="http://blog.gonchuki.com" rel="popup">foobar</a>
      or
      <a href="http://blog.gonchuki.com" rel="popup[600,400]">foobar</a>
      
    where:
      "popup" is the default string token to match against so the popup behavior
              can be attached.
      "[600,400]" is the (optional) size of the newly created window.
      
    optionally, you can specify an "r" parameter like this:
      "[600,400,r]" that will allow the window to be resizable.
*/

var moopop = {
  width: 0,
  height: 0,
  /*
    Function: captureByRel
      standard capturing method, it's autorun onDomReady and you can manually use it
      to capture a different set of popup windows.
      
    Syntax:
      moopop.captureByRel(value, element);
      
    Arguments:
      value - The partial string to match against the rel attribute of your links.
      element - [optional] a DOM element to restrict which links should be processed.
  */
  captureByRel: function(attrVal, parent) {
    this.capture((parent || document).getElements('a[rel*=' + (attrVal || 'popup') +']'));
  },
  
  /*
    Function: capture
      multipurpose function allowing for different methods of capturing the popups.
      
    Syntax:
      moopop.capture(obj, width, height);
      
    Arguments:
      obj - (mixed) can be either a DOM element, an Array of elements or a className.
      width - [optional] (integer) default width for popups without a given size, if
              specified you must also specify the height.
      height - [optional] (integer) default height for popups without a given size.
  */
  capture: function(el, width, height) {
    if ($defined(width) && $defined(height)) {
      this.width = width;
      this.height = height;
    }

    switch ($type(el)) {
      case 'string':
        el = $$(el);
      case 'element':
      case 'array':
        $splat(el).each(this.add_pop_to, this);
    }

    this.width = null;
    this.height = null;
  },
  
  /*
    Function: add_pop_to
      Primarily used internally but you can also use it to manually attach the popup
      behavior to a single DOM element.
      
    Syntax:
      moopop.add_pop_to(element);
      
    Arguments:
      element - a DOM element to process.
  */
  add_pop_to: function(el) {
    el.addEvent('click', function(e){ e.stop(); this.popup(el); }.bind(this));

    var size = el.get('rel').match(/\[(\d+),\s*(\d+)/) || ['', this.width, this.height];
    var resizable = el.get('rel').match(/,(r)/) || [];

    if (size[1]) el.store('popupprops', 'width=' + size[1] + ', height=' + size[2] + (resizable[1] ? ', scrollbars=yes, resizable=yes' : '') );
  },
  
  /*
    Function: popup
      Triggers the popup behavior on a given link. Used internally but you can also use it to
      force a given unprocessed link to open in a new window.
      
    Syntax:
      moopop.popup(element);
      
    Arguments:
      element - a DOM element to process.
  */
  popup: function(el) {
    window.open(el.get('href'), el.get('name') || '', el.retrieve('popupprops') || '');
  }
};

/*
  process all links with rel="popup" by default.
*/
window.addEvent('domready', function () {
  moopop.captureByRel('popup');
});