(function(){

  

  // Make pagination
  const temp = new WeakMap();
  
  const pagination = {
    index: [],
    currentSet: 0,
    indexLabels: undefined,
    tbody: undefined,
    infobox: undefined,
    init(isReload) {

      if(isReload === undefined) {
        this.tbody = document.getElementById("rowsBody");
        this.infobox = document.getElementById("infoBox");
        this.assignEvents();
      }
      this.prepareIndex(isReload);
      this.setDisplay();
      this.prepereIndexLabels(isReload)
      return true;

    },
    indexSetExist() {
   
      try {

        if(this.currentSet === undefined || this.currentSet === null) {
       
            throw new Error(`Current set isn't defined`);
          
        } else if(typeof this.currentSet !== "number"){
  
            throw new Error(`Index set must be type of number, ${typeof this.currentSet} given`);
            
        } else if(!temp.has(this.index)) {
  
            throw new Error("Index wasn't set");
        
        } else if(!temp.get(this.index)[this.currentSet]) {

            throw new Error(`No such set(as: ${this.currentSet})`);
  
        } else return true;

      } catch(e) {

          throw ["Is index set function", e];

      }
      

    },
    setDisplay() {

      try {

        this.indexSetExist();
        this.clearDisplay();

        temp.get(this.index)[this.currentSet].forEach((el) => {

          this.tbody.appendChild(el)
        
        })

        return true;

      } catch(e) {

          console.group("Pagination - display function");
          if(Array.isArray(e)) {

            console.info(e[0]);
            console.error(e[1]);

          } else console.error(e);

          console.groupEnd();
          return false;

      }
  
    },
    clearDisplay() {

      while(this.tbody.firstElementChild) {
        this.tbody.removeChild(this.tbody.firstElementChild);
      }

      return true;

    },
    assignEvents: function() {

      this.tbody.addEventListener("click", (event) => {

        let target = event.target;
        if(target.localName === "button" && target.hasAttribute("table_row") && target.getAttribute("db_row")) {

          target.setAttribute("disabled", true);
          this.removeRow(target.getAttribute("table_row"), target.getAttribute("db_row"));

        }

      }); 

      const paginationList = document.getElementById("pagination");
      paginationList.addEventListener("click", (event) => {

        let target = event.target;
        if(target.localName === "a" && target.hasAttribute("index")) {

          let index = parseInt(target.getAttribute("index"));
          this.currentSet = index;
          this.setDisplay();
          this.removeIndexClass("active");
          target.parentNode.classList.add("active");

        }

      }); 

      return true;

    },
    prepareIndex: function(isReload) {

      if(!isReload) temp.set(this.index, []);

      let index = [];
      let list = isReload
        ? temp.get(this.index).flat()
        : Array.from(document.querySelectorAll("[el_row]"));
      
      do {

        index.push(list.splice(0, 10));

      } while(list.length > 0)

      temp.set(this.index, index);
     
      return true;

    },
    prepereIndexLabels(isReload) {

      if(isReload === undefined) {

        const labels = [];
        temp.set(labels, Array.from(document.querySelectorAll("[indexlabel]")));
        temp.get(labels)[0].classList.add("active");
        this.indexLabels = labels;

      }

      let indexLength = temp.get(this.index).length 
      let labelsLength = temp.get(this.indexLabels).length
      let difference = labelsLength - indexLength;

      if(difference > 0) {

        let labelsToRemove = temp.get(this.indexLabels).splice(parseInt(`-${difference}`), labelsLength) 
        labelsToRemove.forEach(function(label) {
          label.parentNode.removeChild(label);
        });
        
      }

      return true;

    },
    /** Remove class from every index set
     * @param{String} indexID
     * @param{String} className
     * returns Boolean
     */
    removeIndexClass(className) {

      try {

        if(!className) throw "Missing class name";
        if(typeof className !== "string") throw `ClassName must be type of string, ${typeof className} given`
        if(!this.indexLabels || !temp.has(this.indexLabels)) throw "Index labels are missing";
 
        temp.get(this.indexLabels).filter(function(el) {

          return el.classList.contains(className);

        }).forEach((el) => {
      
            el.classList.remove(className);

        })

        return true;

      } catch(e) {

          console.group("Pagination - remove class function");
          if(Array.isArray(e)) {

            console.info(e[0]);
            console.error(e[1]);

          } else console.error(e);

          console.groupEnd();
          return false;

      }

    },
    /**
     * Remove row from DB, cache and DOM
     * @param{*} tabelRow id of row in DOM table
     * @param{*} dbRow id of row in DB table
     */
    removeRow(tableRow, dbRow) {

      if(!tableRow && typeof parseInt(tableRow) === "number") {
        throw new Error(`Parameter tableRow have to be a number, receive: ${typeof tableRow}`)
      }
      if(!dbRow && typeof parseInt(dbRow) === "number") {
        throw new Error(`Parameter dbRow have to be a number, receive: ${typeof tableRow}`)
      }

      const wrapper = this.infobox;

      // Remove from DB
      let req = new XMLHttpRequest();
      req.addEventListener("load", reqListener);
      req.open("GET", `/lista?call=removePost&pID=${dbRow}`);
      req.setRequestHeader("X-Requested-With","XMLHttpRequest");
      req.send();
      function reqListener() {

        // Display response message
        let box = document.createElement("div");
        box.classList.add("auto-close");
        box.innerHTML = this.responseText;
        wrapper.appendChild(box);
        autoCloseBox.init();
        
      }

      // Remove from cache and DOM
      temp.get(this.index)[this.currentSet].forEach((node, elIndex) => {
            
        if(tableRow == node.getAttribute("el_row")) {
          this.tbody.removeChild(node);
          temp.get(this.index)[this.currentSet].splice(elIndex, 1); 
        }
        
      });
      
      if(this.currentSet !== 0) {
        
        // Is set is empty 
        if(temp.get(this.index)[this.currentSet].length === 0) {
        
          this.currentSet--;
          this.removeIndexClass("active");
          // Minus twoe two because one set is to remove and one from length
          let labelsLength = temp.get(this.indexLabels).length -2;
          let label = temp.get(this.indexLabels)[labelsLength];
          label.classList.add("active");
 
        }
  
      }
      this.init("reload");
     
    }
    
  }

  pagination.init();

})()