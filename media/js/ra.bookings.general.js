/* 
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 * 
 * EW     an RA event or walk in ramblers library format
 * ESC    a collection of booking records , EVB
 * EVB    a booking record for an event,  an object
 * NBI    a new booking information for one user
 * BLC    a collection of bookings, collection of BLI
 * BLI    the user information booking for a user
 * WLC    a collection of waiting records, collection of WLI
 * WLI    the user information about someone on waiting list
 */
var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}

if (typeof (ra.bookings) === "undefined") {
    ra.bookings = {};
}

// general functions
ra.bookings.serverAction = function (self, action, dataObj, fcn) {
    var url;
    if (dataObj === null) {
        dataObj = {noInput: true};
    }
    switch (action) {
        case 'getEventsSummary':
            url = "index.php?option=com_ra_eventbooking&view=getallbookings&format=json";
            break;
        case 'getSingleEvent':
            url = "index.php?option=com_ra_eventbooking&view=getbookingstatus&format=json";
            break;
        case 'DisableEvent':
            url = "index.php?option=com_ra_eventbooking&view=disableevent&format=json";
            break;
        case 'SubmitBooking':
            url = "index.php?option=com_ra_eventbooking&view=submitbooking&format=json";
            break;
        case 'Waiting':
            url = "index.php?option=com_ra_eventbooking&view=waiting&format=json";
            break;
        case 'EventChanged':
            url = "index.php?option=com_ra_eventbooking&view=eventchanged&format=json";
            break;
        case 'AdminEmailAllBooking':
            url = "index.php?option=com_ra_eventbooking&view=adminemailallbook&format=json";
            break;
        case 'Adminemailsinglebooking':
            url = "index.php?option=com_ra_eventbooking&view=adminemailsinglebook&format=json";
            break;
        case 'AdminDeleteSingleBooking':
            url = "index.php?option=com_ra_eventbooking&view=admindeletesinglebook&format=json";
            break;
        case 'AdminEmailAllWaiting':
            url = "index.php?option=com_ra_eventbooking&view=adminemailallwait&format=json";
            break;
        case 'AdminEmailSingleWaiting':
            url = "index.php?option=com_ra_eventbooking&view=adminemailsinglewait&format=json";
            break;
        case 'AdminDeleteSingleWaiting':
            url = "index.php?option=com_ra_eventbooking&view=admindeletesinglewait&format=json";
            break;
        case'AdminChangePaid':
            url = "index.php?option=com_ra_eventbooking&view=adminchangepaid&format=json";
            break;
        case'AdminEmailBookingList':
            url = "index.php?option=com_ra_eventbooking&view=adminemailbookinglist&format=json";
            break;
        case 'VerifyEmail':
            url = "index.php?option=com_ra_eventbooking&view=verifyemail&format=json";
            break;
        case 'NotifyListEmail':
            url = "index.php?option=com_ra_eventbooking&view=notifylistemail&format=json";
            break;
        default:
            ra.showMsg(action);
            return;
    }
    url = ra.baseDirectory() + url;
    var formData = new FormData();
    formData.append("data", JSON.stringify(dataObj));
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onload = function () {
        if (xmlhttp.readyState === 4) {
            const response = JSON.parse(xmlhttp.responseText);
            response.status = xmlhttp.status;
            if (response.status === 200) {
                ra.bookings.displayFeedback(response.data.feedback);
                fcn(self, response);
            } else {
                ra.showMsg('Whoops - something went wrong [' + action + ']: ' + response.message);
            }
        }
    };
    xmlhttp.open("POST", url, true);
    xmlhttp.send(formData);
};

ra.bookings.addTextTag = function (tag, ele = 'div', text = '') {
    var ele = document.createElement(ele);
    ele.innerHTML = text;
    tag.appendChild(ele);
};
ra.bookings.displayFeedback = function (feedback) {
    if (feedback === null) {
        ra.showMsg('Invalid response from server, feedback is null');
        return;
    }
    if (feedback.length < 1) {
        return;
    }
    var div = document.createElement("div");
    div.classList.add('ra');
    div.classList.add('booking');
    div.classList.add('feedback');
    div.style.display = "inline-block";
    ra.modals.createModal(div, false);
    if (typeof feedback === 'string') {
        var div1 = document.createElement("div");
        div1.innerHTML = feedback;
        div.appendChild(div1);
        return;
    }
    // array
    feedback.forEach(item => {
        var div1 = document.createElement("div");
        div1.innerHTML = item;
        div.appendChild(div1);

    });
};
ra.bookings.displayEmailIcon = function (tag, desc, eventTag, eventName, data = null) {
    var span = document.createElement("span");
    span.classList.add('ra', 'bookings', 'envelope');
    span.setAttribute("title", desc);
    tag.appendChild(span);

    span.addEventListener('click', (e) => {
        let event = new Event(eventName);
        event.raData = data;
        eventTag.dispatchEvent(event);
    });

};
ra.bookings.displayDeleteIcon = function (tag, desc, eventTag, eventName, data = null) {
    var span = document.createElement("span");
    span.classList.add('ra', 'bookings', 'delete');
    span.setAttribute("title", desc);
    tag.appendChild(span);

    span.addEventListener('click', (e) => {
        let event = new Event(eventName);
        event.raData = data;
        eventTag.dispatchEvent(event);
    });
};

ra.bookings.inputFields = function () {
    this.addHeader = function (tag, headTag, label, helpFunction = null) {
        var heading = document.createElement(headTag);
        heading.innerHTML = label;
        heading.title = 'Click to open or close section';
        tag.appendChild(heading);
        if (helpFunction !== null) {
            new ra.help(heading, helpFunction).add();
        }
        return heading;
    };
    this.addText = function (tag, divClass, label, raobject, property, placeholder = '', helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', 'booking label');
        _label.textContent = label;
        var inputTag = document.createElement('input');
        inputTag.setAttribute('class', 'booking input');
        inputTag.setAttribute('type', 'text');
        inputTag.setAttribute('placeholder', placeholder);
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("input", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        itemDiv.appendChild(_label);
        itemDiv.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return inputTag;
    };
    this.addNumber = function (tag, divClass, label, raobject, property, helpFunction = null) {
        var _label = document.createElement('label');
        _label.setAttribute('class', 'booking label');
        _label.textContent = label;
        var inputTag = document.createElement('input');
        inputTag.setAttribute('class', 'booking input');
        inputTag.setAttribute('type', 'text');
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.value = raobject[property];
        }
        inputTag.addEventListener("input", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        tag.appendChild(_label);
        tag.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(_label, helpFunction).add();
        }

        //   var inputTag = this.addText(tag, divClass, label, raobject, property, '', helpFunction);
        inputTag.setAttribute('type', 'number');
        inputTag.setAttribute('step', '.01');
        return inputTag;
    };
    this.addNumberSelect = function (tag, divClass, label, raobject, property, range, helpFunction = null) {
        var _label = document.createElement('label');
        _label.setAttribute('class', 'booking label');
        _label.textContent = label;
        var inputTag = document.createElement('select');
        var first = true;
        var no = 0;
        for (let i = range.min; i < range.max + 1; i++) {
            if (i === range.current) {
                continue;
            }
            var opt = document.createElement('option');
            opt.value = i;
            no += 1;
            if (i === 1) {
                opt.innerHTML = i.toString() + " attendee";
            } else {
                opt.innerHTML = i.toString() + " attendees";
            }
            if (first) {
                first = false;
                inputTag.value = i;
                raobject[property] = i;
            }
            inputTag.appendChild(opt);
        }
        if (no === 0) {
            return null;
        }
        inputTag.setAttribute('class', 'booking input');
        inputTag.raobject = raobject;
        inputTag.raproperty = property;

        inputTag.addEventListener("input", function (e) {
            e.target.raobject[e.target.raproperty] = e.target.value;
        });
        tag.appendChild(_label);
        tag.appendChild(document.createElement('br'));
        tag.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(_label, helpFunction).add();
        }
        return inputTag;
    };
    this.addEmail = function (tag, divClass, label, raobject, property, placeholder = '', helpFunction = null) {
        var inputTag = this.addText(tag, divClass, label, raobject, property, placeholder, helpFunction);
        inputTag.setAttribute('type', 'email');
        inputTag.addEventListener("input", function (e) {
            e.target.value = e.target.value.toLowerCase();
        });
        return inputTag;
    };
    this.addtelephone = function (tag, divClass, label, raobject, property, placeholder = '', helpFunction = null) {
        var inputTag = this.addText(tag, divClass, label, raobject, property, placeholder, helpFunction);
        inputTag.setAttribute('type', 'tel');
        return inputTag;
    };
    this.addComment = function (tag, divClass, label, comment, helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        if (label !== '') {
            var _label = document.createElement('label');
            _label.setAttribute('class', 'booking label');
            _label.textContent = label;
            itemDiv.appendChild(_label);
        }
        var inputTag = document.createElement('span');
        inputTag.setAttribute('class', 'booking input');
        inputTag.textContent = comment;
        itemDiv.appendChild(inputTag);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        return inputTag;
    };
    this.addButton = function (tag, divClass, label, helpFunction = null) {
        var button = document.createElement('span');
        button.innerHTML = label;
        if (divClass !== null) {
            divClass.forEach(c => {
                button.classList.add(c);
            });

        }
        tag.appendChild(button);
        if (helpFunction !== null) {
            new ra.help(button, helpFunction).add();
        }
        return button;
    };
    this.addHtmlArea = function (tag, divClass, label, rows, raobject, property, placeholder = '', helpFunction = null) {
        var itemDiv = document.createElement('div');
        itemDiv.setAttribute('class', divClass);
        tag.appendChild(itemDiv);
        var _label = document.createElement('label');
        _label.setAttribute('class', ' booking');
        _label.textContent = label;
        itemDiv.appendChild(_label);
        if (helpFunction !== null) {
            new ra.help(itemDiv, helpFunction).add();
        }
        var container = document.createElement('div');
        itemDiv.appendChild(container);
        container.setAttribute('class', 'booking quill');
        var inputTag = document.createElement('div');
        container.appendChild(inputTag);
        inputTag.style.width = '95%';
        inputTag.raobject = raobject;
        inputTag.raproperty = property;
        if (raobject.hasOwnProperty(property)) {  // Initialise value
            inputTag.innerHTML = raobject[property];
        }
        var quill = this.addQuill(inputTag);
        quill.on('text-change', function (delta, oldDelta, source) {
            raobject[property] = quill.getSemanticHTML();
        });
        quill.clipboard.addMatcher(Node.ELEMENT_NODE, function (node, delta) {
            var plaintext = node.innerText;
            var Delta = Quill.import('delta');
            return new Delta().insert(plaintext);
        });
        return inputTag;
    };
    this.addQuill = function (container) {
        var toolbarOptions = [[{'header': [false, 1, 2, 3]}],
            ['bold', 'italic', 'underline', 'strike', 'link'],
            [{'list': 'ordered'}, {'list': 'bullet'}]
        ];
        var quill = new Quill(container, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
            }
        });
        return quill;
    };
};
//////////////////////////////////////////////////////////////////////

ra.bookings.user = function (user) {
    this.canEdit = user.canEdit;
    this.md5Email = user.email;
    this.id = user.id;
    this.name = user.name;
};
ra.bookings.settings = function (settings) {
    this.guest = settings.guest;
    this.group_contact_name = settings.group_contact_name;
    this.group_contact_email = settings.group_contact_email;
    this.maxattendees = settings.maxattendees;
    this.maxguestattendees = settings.maxguestattendees;
    this.userlistvisibletoguests = settings.userlistvisibletoguests;
    this.userlistvisibletousers = settings.userlistvisibletousers;
    this.waitinglist = settings.waitinglist;
    this.display = function (tag) {
        var tags = [
            {parent: 'root', tag: 'h3', innerHTML: 'Global settings'},
            {name: 'general', parent: 'root', tag: 'ul'},
            {parent: 'general', tag: 'li', innerHTML: 'Booking contact'},
            {name: 'contact', parent: 'general', tag: 'ul'},
            {name: 't1', parent: 'general', tag: 'li', innerHTML: 'Logged in users'},
            {name: 'list', parent: 'general', tag: 'ul'},
            {name: 't2', parent: 'general', tag: 'li', innerHTML: 'Guest users'},
            {name: 'guestlist', parent: 'general', tag: 'ul'}
        ];
        var elements = ra.html.generateTags(tag, tags);
        if (this.group_contact_name !== '') {
            this.addLi(elements.contact, this.group_contact_name);
        } else {
            this.addLi(elements.contact, "No booking contact defined");
        }
        this.addLi(elements.list, 'Max places that can be booked: ' + this.maxattendees);
        if (this.guest) {
            this.addLi(elements.guestlist, "Guest may book places");
            this.addLi(elements.guestlist, "Max places that can be booked: " + this.maxguestattendees);
        } else {
            this.addLi(elements.guestlist, "Only logged on users may book places");
        }
        if (this.userlistvisibletousers) {
            this.addLi(elements.list, "Logged on users can see who has booked places");
        } else {
            this.addLi(elements.list, "Logged on users <b>cannot</b> see who has booked places");
        }
        if (this.userlistvisibletoguests) {
            this.addLi(elements.guestlist, "Guest users can see who has booked places");
        } else {
            this.addLi(elements.guestlist, "Guest users <b>cannot</b> see who has booked places");
        }
        if (this.waitinglist) {
            this.addLi(elements.general, "Notify list is allowed");
        } else {
            this.addLi(elements.general, "Notify list is not allowed");
        }
    };
    this.displayUser = function (tag, userId) {
        if (userId === 0) {
            if (this.guest) {
                ra.bookings.addTextTag(tag, 'div', "Guests may book up to " + this.maxguestattendees + " place(s)");
            } else {
                ra.bookings.addTextTag(tag, 'div', "<b>You must be logged in to book places</b>");
            }
        } else {
            ra.bookings.addTextTag(tag, 'div', "Logged in users may book up to " + this.maxattendees + " place(s)");
        }
    };
    this.addLi = function (tag, text) {
        var ele = document.createElement('li');
        ele.innerHTML = text;
        tag.appendChild(ele);
    };
    this.canDisplayBookingList = function (user) {
        if (this.canEdit) {
            return true;
        }
        var listUsers = false;
        if (user.id > 0 && this.userlistvisibletousers) {
            listUsers = true;
        }
        if (user.id === 0 && this.userlistvisibletoguests) {
            listUsers = true;
        }
        return listUsers;
    };
    this.canDisplayWaitingList = function (user) {
        if (!this.waitinglist) {
            return false;
        }
        return  this.canDisplayBookingList(user);
    };
};
// Event Summary Collection
ra.bookings.esc = function () {
    this.items = [];
    this.addItem = function (item) {
        this.items.push(item);
    };

    this.process = function (events) {
        events.forEach(event => {
            this.addItem(new ra.bookings.evb(event));
        });
    };
    this.removeItem = function (ewid) {
        let noEwid = this.items.filter(el => el.ewid !== ewid);
        this.items = noEwid;
    };
    // display summary table
    this.displaySummary = function (tag, canEdit = false) {
        if (this.items.length === 0) {
            var h = document.createElement("h4");
            h.innerHTML = 'No events with bookings were found';
            tag.appendChild(h);
            return;
        }
        var format = [{"title": "ID", "options": {align: "left"}, field: {type: 'text', filter: false, sort: true}},
            {"title": "Date", "options": {align: "left"}, field: {type: 'date', filter: false, sort: true}},
            {"title": "Title", "options": {align: "left"}, field: {type: 'text', filter: false, sort: true}},
            {"title": "Places", "options": {align: "left"}, field: {type: 'number', filter: false, sort: true}},
            {"title": "Booked", "options": {align: "left"}, field: {type: 'number', filter: false, sort: true}},
            {"title": "Waiting", "options": {align: "left"}, field: {type: 'number', filter: false, sort: true}},
            {"title": "Disable", "options": {align: "right"}}];
        var title = document.createElement("h3");
        title.textContent = "Events/walks with a booking record";
        tag.appendChild(title);
        var table = new ra.paginatedTable(tag);
        table.tableHeading(format);
        this.items.forEach(event => {
            event.displaySummary(table, format, canEdit);
        });
        table.tableEnd();
    };
};
// Booking information for an RA event
ra.bookings.evb = function (value) {
    this.ewid = value.event_id;
    this.max_places = value.max_places;
    this.blc = new ra.bookings.blc();
    this.blc.process(value.blc);
    this.event_contact_md5Email = value.event_contact_email;
    this.event_contact_name = value.event_contact_name;
    this.event_data = value.event_data;
    this.payment_details = value.payment_details;
    this.payment_required = value.payment_required;
    this.wlc = new ra.bookings.wlc();
    this.wlc.process(value.wlc);
    this.event = ra.walk.getEventID(this.ewid);
// Display summary table row
    this.displaySummary = function (table, format, canEdit) {

        table.tableRowStart();
        table.tableRowItem(this.ewid, format[0]);
        if (this.event !== null) {
            var date = this.event.getEventValue('{dowShortddmm}');
            var sortDate = this.event.basics.walkDate;
            var title = this.event.getEventValue('{title}');
            table.tableRowItem(date, format[1], sortDate);
            var ele = table.tableRowItem(title, format[2]);
            ele.setAttribute('data-eventid', this.ewid);
            ele.classList.add('link-button', 'tiny', 'button', 'mintcake');
            ele.addEventListener("click", function (e) {
                var id = e.currentTarget.getAttribute('data-eventid');
                ra.walk.displayWalkID(e, id);
            });
        } else {
            table.tableRowItem('', format[1]);
            table.tableRowItem('Event not found', format[2]);
        }
        var places = this.max_places;
        if (places === 0) {
            places = 'Unlimited';
        }
        table.tableRowItem(places, format[3]);
        table.tableRowItem(this.blc.noAttendees(), format[4]);
        table.tableRowItem(this.wlc.noWaiting(), format[5]);
        if (canEdit && this.event === null) {
            var disable = table.tableRowItem('Disable', format[6]);
            disable.setAttribute('data-eventid', this.ewid);
            disable.classList.add('link-button', 'tiny', 'button', 'mintcake');
            var self = this;
            disable.addEventListener("click", function (e) {
                var id = e.currentTarget.getAttribute('data-eventid');
                ra.bookings.serverAction(self, 'DisableEvent', {ewid: id}, self._BookingDisableResult);
            });
        } else {
            table.tableRowItem('');
        }
        table.tableRowEnd();
    };
    this._BookingDisableResult = function (self, results) {
        if (results.status !== 200) {
            ra.showMsg('Unable to disable event/booking record');
            return;
        }
        let event = new Event("disableEvent"); // 
        event.raData = {};
        event.raData.ewid = results.data.ewid;
        document.dispatchEvent(event);

    };
    this.calcMaxPlaces = function (user, settings) {
        var totalBooked = this.blc.noAttendees();
        var booking = this.blc.isPresent(user.md5Email);
        var noBooked = 0;
        if (booking !== null) {
            noBooked = booking.attendees;
        }
        var availablePlaces = 9999;
        if (this.max_places > 0) {
            availablePlaces = this.max_places - totalBooked + noBooked;
        }

        var maxAttendees;
        if (user.id > 0) {
            maxAttendees = settings.maxattendees;
        } else {
            maxAttendees = settings.maxguestattendees;
        }
        if (availablePlaces < maxAttendees) {
            maxAttendees = availablePlaces;
        }
        return maxAttendees;
    };
    this.listAttendees = function (tag, settings, user) {
        var options = {
            guest: settings.guest,
            canEdit: user.canEdit,
            displayPaid: this.payment_required && user.canEdit};
        this.blc.list(tag, options);
    };
    this.listWaiting = function (tag, settings, user) {
        var options = {
            guest: settings.guest,
            canEdit: user.canEdit};
        this.wlc.list(tag, options);
    };
    this.displayBookingStatus = function (tag, settings, user) {

        if (!this.allowBookingForm(settings, user)) {
            ra.bookings.addTextTag(tag, 'div', "<b>You must be logged in to book places</b>");
        }
        if (this.payment_required) {
            this.displayPaymentDetails(tag);
        } else {
            ra.bookings.addTextTag(tag, 'div', "No payment required");
        }
        if (this.max_places === 0) {
            ra.bookings.addTextTag(tag, 'div', "Unlimited number of places");
        } else {
            ra.bookings.addTextTag(tag, 'div', "Total number of places available: " + this.max_places);
        }
        var no = this.noAttendees();
        switch (no) {
            case 0:
                ra.bookings.addTextTag(tag, 'div', "There are no bookings so far");
                break;
            case this.max_places:
                ra.bookings.addTextTag(tag, 'div', "This event if fully booked");
                break;
            default:
                ra.bookings.addTextTag(tag, 'div', "There are currently " + no + " place(s) booked.");
        }
    };
    this.displayPaymentDetails = function (tag) {
        var tags = [
            {parent: 'root', tag: 'div', attrs: {class: 'ra bookings'}, innerHTML: 'A payment is required for this walk/event:'},
            {name: 'details', parent: 'root', tag: 'div', attrs: {class: 'booking howtopay'}},
            {parent: 'details', tag: 'div', innerHTML: 'How to pay'},
            {parent: 'details', tag: 'div', attrs: {class: 'bookingitem walkitem payment'}, style: {'margin-left': '10px'}, innerHTML: this.payment_details},
            {parent: 'root', tag: 'div', style: {clear: 'both'}}
        ];
        ra.html.generateTags(tag, tags);
    };
    this.getBooking = function (md5Email) {
        return  this.blc.isPresent(md5Email);
    };
    this.getWaiting = function (md5Email) {
        return this.wlc.isPresent(md5Email);
    };
    this.noAttendees = function () {
        return this.blc.noAttendees();
    };
    this.allowBookingForm = function (settings, user) {
        var placesOkay = true;
        var no = this.blc.noAttendees();
        if (no >= this.max_places && this.max_places !== 0) {
            if (!settings.waitinglist) {
                placesOkay = false;
            }
        }
        var userOkay = false;
        if (user.id > 0) { // user logged in
            userOkay = true;
        } else {
            if (settings.guest) {
                userOkay = true;
            }
        }
        if (userOkay && placesOkay) {
            return true;
        }
        return false;
    };
    this.allowWaitingForm = function (settings, user) {
        var placesOkay = true;
        var no = this.blc.noAttendees();
        if (no >= this.max_places && this.max_places !== 0) {
            if (!settings.waitinglist) {
                placesOkay = false;
            }
        }
        var userOkay = false;
        if (user.id > 0) { // user logged in
            userOkay = true;
        } else {
            if (settings.guest) {
                userOkay = true;
            }
        }
        if (userOkay && placesOkay) {
            return true;
        }
        return false;
    };
    this.allowAttendeesList = function (settings, user) {

        var no = this.blc.noAttendees();
        if (no === 0) {
            return false;
        }
        if (user.canEdit) {
            return true;
        }
        if (user.id > 0) { // user logged in.
            return settings.userlistvisibletousers;
        } else {
            return settings.userlistvisibletoguests;
        }
    };
};
// Booking List Collection
ra.bookings.blc = function () {
    this.items = [];
    this.addItem = function (b) {
        this.items.push(b);
    };
    this.process = function (values) {
        values.forEach(value => {
            this.addItem(new ra.bookings.bli(value));
        });
    };
    this.noAttendees = function () {
        var no = 0;
        this.items.forEach(b => {
            no += b.noAttendees();
        });
        return no;
    };
    this.isPresent = function (md5Email) {
        for (let item of this.items) {
            if (item.isPresent(md5Email)) {
                return item;
            }
        }
        return null;
    };
    this.list = function (tag, options) {
        if (this.items.length === 0) {
//            var c = document.createElement("p");
//            c.innerHTML = 'There are no bookings ';
//            tag.appendChild(c);
            return;
        }
        var tags = [
            {name: 'base', parent: 'root', tag: 'details'},
            {name: 'button', parent: 'base', tag: 'summary', attrs: {class: 'link-button tiny button mintcake'}, innerHTML: 'Bookings so far'},
            {name: 'list', parent: 'base', tag: 'div', style: {clear: 'both'}}
        ];
        var elements = ra.html.generateTags(tag, tags);
        var table = document.createElement("table");
        elements.list.appendChild(table);
        var cols = [];
        cols.push("Name");
        if (options.guest) {
            cols.push("Status");
        }
        if (options.canEdit) {
            cols.push("Telephone");
        }
        cols.push("Places");
        if (options.displayPaid) {
            cols.push("Paid");
        }
        if (options.canEdit) {
            cols.push("Actions");
        }
        ra.html.addTableRow(table, cols, 'th');
        this.items.forEach(item => {
            item.list(tag, table, options);
        });
        if (options.canEdit) {
            var emailallc = document.createElement("div");
            emailallc.innerHTML = "Email all those who have booked&nbsp;&nbsp;";
            elements.list.appendChild(emailallc);
            ra.bookings.displayEmailIcon(emailallc, "Email all those who have booked", tag, "AdminEmailAllBooking");

            var email = document.createElement("div");
            email.innerHTML = "Email above booking list to me&nbsp;&nbsp;";
            elements.list.appendChild(email);
            ra.bookings.displayEmailIcon(email, "Email above booking list to me", tag, "AdminEmailBookingList");
        }
    };
};
// Booking list item
ra.bookings.bli = function (value) {
    this.id = value.id;
    this.name = value.name;
    this.md5Email = value.md5Email;
    this.telephone = value.telephone;
    this.attendees = parseInt(value.noAttendees);
    this.paid = value.paid;

    this.isPresent = function (md5Email) {
        return md5Email === this.md5Email;
    };
    this.noAttendees = function () {
        return this.attendees;
    };
    this.list = function (eventTag, tag, options) {
        var item = document.createElement("tr");
        tag.appendChild(item);
        var cols = [];
        cols.push(this.name);
        if (options.guest) {
            if (this.id > 0) {
                cols.push("Registered");
            } else {
                cols.push("Guest");
            }
        }
        if (options.canEdit) {
            cols.push(this.telephone);
        }
        cols.push(this.attendees);
        if (options.displayPaid) {
            cols.push(this.getPaid(options, eventTag, this));
        }
        if (options.canEdit) {
            var self = this;
            var span = document.createElement("span");
            ra.bookings.displayDeleteIcon(span, "Delete this booking", eventTag, "deleteBooker", {user: self});
            ra.bookings.displayEmailIcon(span, "Email this user", eventTag, "emailSingleBooker", {user: self});
            cols.push(span);
        }
        ra.html.addTableRow(tag, cols);
    };

    this.getPaid = function (options, eventTag, user) {
        var span = document.createElement("span");
        span.classList.add('ra', 'bookings', 'paid');
        span.innerHTML = this.paid;
        if (options.canEdit) {
            span.title = 'Paid - click to change';
            span.classList.add('edit');
            span.addEventListener('click', (e) => {
                let event = new Event('changePaid');
                event.raData = user;
                eventTag.dispatchEvent(event);
            });
        }
        return span;
    };
};
// Waiting/Notification list collection
ra.bookings.wlc = function () {
    this.items = [];
    this.addItem = function (wl) {
        this.items.push(wl);
    };
    this.process = function (values) {
        values.forEach(value => {
            this.addItem(new ra.bookings.wli(value));
        });
    };
    this.noWaiting = function () {
        return this.items.length;
    };
    this.isPresent = function (md5Email) {
        for (let item of this.items) {
            if (item.isPresent(md5Email)) {
                return item;
            }
        }
        return null;
    };

    this.list = function (tag, options) {
        if (this.items.length === 0) {
            return;
        }
        var tags = [
            {name: 'base', parent: 'root', tag: 'details'},
            {name: 'button', parent: 'base', tag: 'summary', attrs: {class: 'link-button tiny button mintcake'}, innerHTML: 'Notify me list'},
            {name: 'list', parent: 'base', tag: 'div', style: {clear: 'both'}}
        ];
        var elements = ra.html.generateTags(tag, tags);
        if (options.canEdit) {
            var emailallc = document.createElement("span");
            emailallc.innerHTML = "Email all those on list";
            emailallc.style.paddingRight = "10px";
            elements.list.appendChild(emailallc);
            ra.bookings.displayEmailIcon(elements.list, "Email all those on list", tag, "AdminEmailAllWaiting");
        }
        var table = document.createElement("table");
        elements.list.appendChild(table);
        var cols = [];
        cols.push("Name");
        if (options.guest) {
            cols.push("Status");
        }
        if (options.canEdit) {
            cols.push("Telephone");
        }
        if (options.canEdit) {
            cols.push("Actions");
        }
        ra.html.addTableRow(table, cols, 'th');
        this.items.forEach(item => {
            item.list(tag, table, options);
        });
    };
};
// Waiting list item
ra.bookings.wli = function (value) {
    this.id = value.id;
    this.name = value.name;
    this.md5Email = value.md5Email;
    this.telephone = value.telephone;
    this.isPresent = function (md5Email) {
        return md5Email === this.md5Email;
    };
    this.list = function (eventTag, tag, options) {
        var item = document.createElement("tr");
        tag.appendChild(item);
        var cols = [];
        cols.push(this.name);
        if (options.guest) {
            if (this.id > 0) {
                cols.push("Registered");
            } else {
                cols.push("Guest");
            }
        }
        if (options.canEdit) {
            cols.push(this.telephone);
            var self = this;
            var span = document.createElement("span");
            ra.bookings.displayDeleteIcon(span, "Delete from list", eventTag, "deleteWaiting", {user: self});
            ra.bookings.displayEmailIcon(span, "Email this user", eventTag, "AdminEmailSingleWaiting", {user: self});
            cols.push(span);
        }
        ra.html.addTableRow(tag, cols);
    };
};