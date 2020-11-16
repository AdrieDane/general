/* Common measurement conversions in PHP
   https://thisinterestsme.com/common-measurement-conversions-php/

   One of the tricky things about being a web developer is that you
   are sometimes forced to accommodate visitors that use different
   measurement systems and formats. Currency symbols, date formats and
   measurement systems; all of them can differ from one user to the
   next. The following PHP code snippets will help you to convert
   between some of the most common measurements (most notably, those
   found in the Imperial System and the Metric System).
*/


// While Pounds (lb) is a popular measurement for weight in the United States; 
// in Europe, they tend to use Kilograms:

// Kilograms to Pounds
function kilogramsToPounds($kg){
    return $kg * 2.20462;
}

// Pounds to Kilograms

function poundsToKilograms($pounds){
    return $pounds * 0.453592;
}

// Stone to Kilograms
function stoneToKilograms($stone){
    return $stone * 6.35029;
}

// Kilometres to Miles
function kilometersToMiles($km){
    return $km * 0.621371;
}

// Miles to Kilometres
function milesToKilometers($miles){
    return $miles * 1.60934;
}

// Pounds to Stone
function poundsToStone($pounds){
    return $pounds * 0.0714286;
}

// Yards to Meters
function yardsToMeters($yards){
    return $yards * 0.9144;
}

// Centimetres to Inches
// There are 39.3700787 inches in a meter:
function centimetersToInches($centimeters){
    return ($centimeters * 0.01) * 39.3700787;
}

// Meters to Miles
function metersToMiles($meters){
    return $meters * 0.000621371;
}

// Inches to Centimetres
// Centimetres = Inches x 2.54
function inchesToCentimeters($inches){
  return $inches * 2.54;
}

// Fahrenheit to Celsius

// As you can see, converting Fahrenheit to Celsius is not as simple as
// the conversions shown above:

function fahrenheitToCelsius($fahrenheit){
    return ($fahrenheit - 32) * 5 / 9;
}

// Celsius to Fahrenheit
// Converting Celsius into Fahrenheit isn't exactly straight forward either:
function celsiusToFahrenheit($celsius){
    return $celsius * 9/5 + 32;
}
