/* 
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 * 
 * EW     an RA event or walk in ramblers library format
 * ESC    a collection of summary booking records , EVB
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

ra.bookings = function (tag, ewid, ew, ics) {
    // ew is passed so that it can be sent to server to create email to user
    this.ewid = ewid;
    this.ew = ew;
    this.ics = ics;
    this.elements = null;
    this.evb = null;
    this.settings = null;
    this.user = null;
    this.formModal = null;
    this.lastNoAttendees = 0;
    this.container = document.createElement("div");
    this.container.classList.add('walkitem');
    this.container.classList.add('bookings');
    tag.appendChild(this.container);

    this.initialise = function () {
        this.container.innerHTML = '';
        var tags = [
            {name: 'bookingButton', parent: 'root', tag: 'span', attrs: {class: 'link-button tiny button mintcake'}, innerHTML: 'Book places/Info', style: {cssFloat: 'right'}},
            {name: 'title', parent: 'root', tag: 'div', innerHTML: '<b>Booking Information</b>'},
            {name: 'message', parent: 'root', tag: 'div', innerHTML: '<i>Retrieving information ...</i>'},
            {name: 'content', parent: 'root', tag: 'div'}
        ];
        this.elements = ra.html.generateTags(this.container, tags);
        var self = this;
        this.elements.bookingButton.style.display = 'none';
        this.elements.bookingButton.addEventListener('click', function () {
            var div = document.createElement("div");
            div.style.display = "inline-block";
            self.formModal = ra.modals.createModal(div, false);
            var form = new ra.bookings.formBooking(self.settings, self.user, self.ewid, self.ew, self.evb, self.ics);
            form.display(div);
        });
        document.addEventListener('bookingInfoChanged', function (e) {
            self.container.innerHTML = '';
            if (self.formModal !== null) {
                self.formModal.close();
            }
            self.initialise();
        });
        var data = {ewid: this.ewid};
        ra.bookings.serverAction(this, 'getSingleEvent', data, (self, results) => {
            self.displayOptions(results);
            self.elements.bookingButton.style.display = '';
        });
    };


    this.displayOptions = function (results) {
        if (results.data === null) {
            this.elements.message.innerHTML = "You do not need to book for this walk/event";
            return;
        }
        this.elements.message.innerHTML = "<b>Booking is required for this walk/event</b>";
        this.evb = new ra.bookings.evb(results.data.evb);
        this.settings = new ra.bookings.settings(results.data.settings);
        this.user = new ra.bookings.user(results.data.user);

        this.evb.displayBookingStatus(this.elements.content, this.settings, this.user);
        this.settings.displayUser(this.elements.content, this.user.id);

        // has ev changed since last view
        // if so ask server to email users of change.
        if (this.ew.admin.dateUpdated.toISOString() > this.evb.event_data.dateUpdated || this.evb.event_data.dateUpdated === null) {
            var data = {ewid: this.ewid,
                ew: JSON.stringify(this.ew),
                ics: self.ics};
            ra.bookings.serverAction(this, 'EventChanged', data, (self, results) => {

            });
        }
        var noAttendees = this.evb.noAttendees();
        // if this is a redisplay after a user action and the no attendees have reduced
        // then we send the notify list an email
        if (noAttendees < this.lastNoAttendees) {
            var data = {ewid: this.ewid,
                ew: JSON.stringify(this.ew)};
            ra.bookings.serverAction(this, 'NotifyListEmail', data, (self, results) => {

            });
        }
        this.lastNoAttendees = noAttendees;
    };
};