/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
window.addEvent('domready', function () {
    document.formvalidator.setHandler('greeting',
        function (value) {
            regex = /^[^0-9]+$/;
            return regex.test(value);
        });
});
