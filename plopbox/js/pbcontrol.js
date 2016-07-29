/*   |
,---.|---.
|   ||   |
|---'`---'
|
PlopBox
Supervising Controller
*/
"use strict";

// Controller Object Constructor
var ControllerConstructor = (function () {
  function controllerObject () {
    var self = this;

    // URI Navigator Object Constructor
    function navigatorObject () {
      var self = this;
      var uri = "/pbindex.php";
      var args = {};

      // Output the URL arguments
      this.args = function () {
        return args;
      }

      // Add a URL Argument
      this.addArgs = function (values) {
        for (var i = 0; i < values.length; i++) {
          for (var arg in values[i]) {
            args[arg] = values[i];
          };
        };
      }

      // Remove a URL Argument
      this.removeArgs = function (values) {
        for (var arg in values) {
          var exists = args.hasOwnProperty(arg);
          if (exists == false) {
            return;
          } else if (exists == true) {
            args.splice(arg, 1);
          };
        };
      }

      // Reset Navigator to default data
      this.reset = function () {
        self.args = {};
        uri = "/pbindex.php";
      }

      // Output the URI with or without Arguments
      this.uri = function () {
        return uri;
      }
    }

    // Create Navigator
    this.nav = new navigatorObject();

    // GET Data from the server
    function getData (nav, model) {
      console.log("GetData= " + nav.uri());
      $.ajax({
        url: nav.uri(),
        type: "get",
        headers: "",
        data: $(nav.args()).serialize(),
        dataType: "json",
        timeout: 30000,
        success: function (json) {
          if (json.error) {
            console.log("Error communicating with the server! " + json.error);
          } else {
            console.log(json);
            model.update(json);
          };
        }
      });
      return false;
    }

    // POST Data from the server
    function postData (nav, model, data = "") {
      console.log("PostData= " + nav.uri());
      $.ajax({
        url: nav.uri(),
        type: "post",
        data: data,
        dataType: "json",
        timeout: 30000,
        success: function (json) {
          if (json.error) {
            console.log("Error communicating with the server! " + json.error);
          } else {
            console.log(json);
            model.update(json);
          };
        }
      });
      return false;
    }

    // Get Initial Data
    getData(this.nav, model);
    console.log("Controller: I'm alive!");
  }

  // Singleton Constructor Functions
  var instance;
  function createController () {
    var object = new controllerObject();
    return object;
  }

  return {
    newController: function () {
      if (!instance) {
        return createController();
      } else {
        return;
      }
    }
  }
})(model);

$(
  function () {
    // Create Controller
    var controller = new ControllerConstructor.newController(model);
  }
)
