var Sayings;
(function (Sayings) {
    var Greeter = /** @class */ (function () {
        function Greeter(message) {
            this.greeting = message;
        }
        Greeter.prototype.greet = function () {
            return "Hello, " + this.greeting;
        };
        return Greeter;
    }());
    Sayings.Greeter = Greeter;
})(Sayings || (Sayings = {}));
var greeter = new Sayings.Greeter("world");
var button = document.createElement('button');
button.innerText = "Say Hello";
button.onclick = function () {
    alert(greeter.greet());
};
document.body.appendChild(button);
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoidHMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJ0cy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQSxJQUFPLE9BQU8sQ0FVYjtBQVZELFdBQU8sT0FBTztJQUNWO1FBRUksaUJBQVksT0FBZTtZQUN2QixJQUFJLENBQUMsUUFBUSxHQUFHLE9BQU8sQ0FBQztRQUM1QixDQUFDO1FBQ0QsdUJBQUssR0FBTDtZQUNJLE1BQU0sQ0FBQyxTQUFTLEdBQUcsSUFBSSxDQUFDLFFBQVEsQ0FBQztRQUNyQyxDQUFDO1FBQ0wsY0FBQztJQUFELENBQUMsQUFSRCxJQVFDO0lBUlksZUFBTyxVQVFuQixDQUFBO0FBQ0wsQ0FBQyxFQVZNLE9BQU8sS0FBUCxPQUFPLFFBVWI7QUFDRCxJQUFJLE9BQU8sR0FBRyxJQUFJLE9BQU8sQ0FBQyxPQUFPLENBQUMsT0FBTyxDQUFDLENBQUM7QUFDM0MsSUFBSSxNQUFNLEdBQUcsUUFBUSxDQUFDLGFBQWEsQ0FBQyxRQUFRLENBQUMsQ0FBQztBQUM5QyxNQUFNLENBQUMsU0FBUyxHQUFHLFdBQVcsQ0FBQztBQUMvQixNQUFNLENBQUMsT0FBTyxHQUFHO0lBQ2IsS0FBSyxDQUFDLE9BQU8sQ0FBQyxLQUFLLEVBQUUsQ0FBQyxDQUFDO0FBQzNCLENBQUMsQ0FBQztBQUNGLFFBQVEsQ0FBQyxJQUFJLENBQUMsV0FBVyxDQUFDLE1BQU0sQ0FBQyxDQUFDIiwic291cmNlc0NvbnRlbnQiOlsibW9kdWxlIFNheWluZ3Mge1xuICAgIGV4cG9ydCBjbGFzcyBHcmVldGVyIHtcbiAgICAgICAgZ3JlZXRpbmc6IHN0cmluZztcbiAgICAgICAgY29uc3RydWN0b3IobWVzc2FnZTogc3RyaW5nKSB7XG4gICAgICAgICAgICB0aGlzLmdyZWV0aW5nID0gbWVzc2FnZTtcbiAgICAgICAgfVxuICAgICAgICBncmVldCgpIHtcbiAgICAgICAgICAgIHJldHVybiBcIkhlbGxvLCBcIiArIHRoaXMuZ3JlZXRpbmc7XG4gICAgICAgIH1cbiAgICB9XG59XG52YXIgZ3JlZXRlciA9IG5ldyBTYXlpbmdzLkdyZWV0ZXIoXCJ3b3JsZFwiKTtcbnZhciBidXR0b24gPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdidXR0b24nKTtcbmJ1dHRvbi5pbm5lclRleHQgPSBcIlNheSBIZWxsb1wiO1xuYnV0dG9uLm9uY2xpY2sgPSBmdW5jdGlvbigpIHtcbiAgICBhbGVydChncmVldGVyLmdyZWV0KCkpO1xufTtcbmRvY3VtZW50LmJvZHkuYXBwZW5kQ2hpbGQoYnV0dG9uKTtcbiJdfQ==