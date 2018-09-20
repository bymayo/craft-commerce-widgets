var ctx = document.getElementById("myChart").getContext("2d");

const colours = {
  abandoned: {
    stroke: '#e67a0b',
    background: 'rgba(230,122,11,0.4)'
  },
  completed: {
    stroke: '#27AE5F',
    background: 'rgba(39,174,95,0.1)'
  }
};

const abandoned = [0, 10, 5, 1, 0, 4, 10];
const completed = [10, 44, 33, 24, 25, 28, 25];
const xAxisData = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];

const myChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: xAxisData,
    datasets: [ {
      label: "Cart Abandoned",
      pointBackgroundColor: colours.abandoned.stroke,
      borderColor: colours.abandoned.stroke,
      pointHighlightStroke: colours.abandoned.stroke,
      borderCapStyle: 'butt',
      data: abandoned,
      fill: false,
      backgroundColor: colours.abandoned.background
    }, {
      label: "Cart Completed",
      fill: false,
      backgroundColor: colours.completed.background,
      pointBackgroundColor: colours.completed.stroke,
      borderColor: colours.completed.stroke,
      pointHighlightStroke: colours.completed.stroke,
      data: completed,
    }]
  },
  options: {
    legend: {
      display: false
   },
    responsive: true,
    scales: {
      yAxes: [{
        stacked: true,
      }]
    },
    animation: {
      duration: 750,
    },
  }
});
