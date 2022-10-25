<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PLANS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, viewport-fit=cover"/>
</head>
<style>
    .center,h2 {
        display: -webkit-flex;
        display: flex;
        justify-content:center;
        align-items:center;
    }
    th, input{
        border:1px solid #000;
        padding-top:0.5rem;
        padding-bottom:0.5rem;
    }
    th:first-child {
        width: 10vw;
    }
    th:nth-child(2) {
        width: 30vw;
    }
    th:nth-child(3) {
        width: 30vw;
    }
    th:nth-child(4) {
        width: 30vw;
    }
</style>
<body>
<h2><u>Plans for being</u></h2>
<div class="center">
    <table>
        <thead>
        <th> Stage </th>
        <th> Buy </th>
        <th> percentage </th>
        <th> Profit </th>
        </thead>
        <tbody id="plans">
        <tr>
            <th>1</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>2</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>3</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>4</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>5</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>6</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>7</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>8</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>9</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>10</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        </tbody>
    </table>
</div>
<div class="center" style="padding-top:0.5rem;padding-bottom:0.5rem;">
    <input id="number" type="number" value="" placeholder="Minimum input amount: 20" style="width:50vw;">
    <button id="enter" style="padding:0.5rem;margin-left:0.5rem;">Enter the amount</button>
</div>
<script>
    // var bits = [0.6, 1.207, 2.648, 5.858, 13.333, 32.051, 100];
    var bits = [0.07, 0.144, 0.298, 0.621, 1.3, 2.745,5.899,13.132, 31.746,100];
    var plans = document.getElementById("plans");
    var number = document.getElementById("number")
    document.getElementById("enter").onclick = function(){
        var amount = Number(number.value.replace(/\s+/g, ''));
        // var earning = 0;
        var costTotal = 0;
        if (number.value != "" && amount < 20) {
            alert("Минимальная сумма ввода: 20")
            return;
        }
        for (var i=0; i<bits.length; i++) {
            var ch = plans.children[i]
            ch.children[0].innerText = i + 1
            ch.children[2].innerText = bits[i] + "%"
            if (number.value.replace(/\s+/g, '') == "") {
                continue;
            }

            var cost = Number(amount * bits[i] / 100).toFixed(2)
            ch.children[1].innerText = cost
            amount -= Number(cost);
            costTotal += Number(cost);
            ch.children[3].innerText = Number(1.95 * cost - costTotal).toFixed(3)
        }
    }
    document.getElementById("enter").click();
</script>
</body>
</html>
