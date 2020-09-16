if(!window.App)
    App = {};

App.extend = function(child, parent)
{
    var f = function() {};
    f.prototype = parent.prototype;

    child.prototype = new f();
    child.prototype.constructor = child;

    child.superclass = parent.prototype;
    child.prototype.superclass = parent.prototype;
    if(parent.prototype.constructor == Object.prototype.constructor)
    {
        parent.prototype.constructor = parent;
    }
};