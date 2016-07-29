// Model Object Constructor
var ModelConstructor = (function () {
function modelObject () {
  var self = this;
  var view = null;

  // Recieve View Object Reference
  this.viewRef = function (viewObject) {
    if (!view) {
      var view = viewObject
    }
  }

  this.update = function () {
    // File Index
    if (pagedata.opcode === 'FileIndex') {
      if (pagedata.statcode == 'ViewDeny' || pagedata.statcode == 'Empty') {

      } else {
        // File Entry Object Constructor
        function itemEntryObject (fileData, itemcount, ID) {
          if (fileData['dir']) {
            this.id = ID;
            this.dir = filedata['dir'];
            this.fileName = fileData['name'];
            this.uri = fileData['path'];
            this.icon = fileData['ficon'];
            this.modTime = fileData['mtime'];
            this.size = fileData['fsize'];
            this.sizefactor = fileData['fsizefactor'];
            this.modTimeUX = filedata['mtimeux'];
          } else {
            this.id = ID;
            this.dir = filedata['dir'];
            this.fileName = fileData['name'];
            this.uri = fileData['path'];
            this.icon = fileData['ficon'];
            this.modTime = fileData['mtime'];
            this.size = fileData['fsize'];
            this.sizefactor = fileData['fsizefactor'];
            this.modTimeUX = filedata['mtimeux'];
          };

          // File Operation Functions
          this.open = function () {

          }

          this.move = function (dest) {

          }

          this.delete = function () {

          }
        }

        // Generate File Entries
        for (i = 0; i < pagedata.itemcount; i++) {
          pagedata.fileEntries[i] = new itemEntryObject(pagedata.filedata[i], pagedata.itemcount, i);
        };
      };

      // Navbar
      var uriarray = nav.uri().split("/");
      var lastnav = "";
      for (i = 0, len = nav.uri().length; i < len; i++) {
        pagedata.navItems[i] = lastnav + "/" + uriarray[i];
        lastnav =+ uriarray[i];
      };

      // Stage Model Data
      var modelData = {
        opcode: pagedata.opcode,
        statcode: pagedata.statcode,
        msg: pagedata.msg,
        crow: pagedata.crow,
        filedata: pagedata.filedata,
        loc: pagedata.loc,
        sort: pagedata.sort
      };

    } else if (pagedata.opcode == 'LoginPage') {
      var modelData = {
        opcode: pagedata.opcode,
        statcode: pagedata.statcode,
        token: pagedata.token,
        msg: pagedata.msg,
      }
    };
    view.update(modelData);
  }
}


// Singleton Constructor Functions
var instance;
function createModel () {
  var object = new modelObject();
  return object;
}

return {
  newModel: function () {
    if (!instance) {
      return createModel();
    } else {
      return;
    }
  }
}
})(model);
