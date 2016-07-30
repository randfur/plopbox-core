/*   |
,---.|---.
|   ||   |
|---'`---'
|
PlopBox
Supervising Controller
*/
"use strict";

var controller = (function() {
  var navigator = (function() {
    var uri = "/pbindex.php";
    var args = {};

    return {
      // Output the URL arguments
      args: function() {
        return args;
      },

      // Add a URL Argument
      addArgs: function(values) {
        for (var i = 0; i < values.length; i++) {
          for (var arg in values[i]) {
            args[arg] = values[i];
          }
        }
      },

      // Remove a URL Argument
      removeArgs: function(values) {
        for (var arg in values) {
          var exists = args.hasOwnProperty(arg);
          if (exists == false) {
            return;
          } else if (exists == true) {
            args.splice(arg, 1);
          }
        }
      },
  
      // Reset Navigator to default data
      reset function() {
        args = {};
        uri = "/pbindex.php";
      },
  
      // Output the URI with or without Arguments
      uri: function() {
        return uri;
      },
    };
  })();

  return {
    // GET Data from the server
    getData: function(model) {
      console.log("GetData= " + navigator.uri());
      $.ajax({
        url: navigator.uri(),
        type: "get",
        headers: "",
        data: $(navigator.args()).serialize(),
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
    },
  
    // POST Data from the server
    postData: function(model, data = "") {
      console.log("PostData= " + navigator.uri());
      $.ajax({
        url: navigator.uri(),
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
    },
  };

  // Get Initial Data
  // We don't have access to model here, call this method from main() where we know about model.
  //getData(model);
})();
