// Enhanced animation system for jungle playgrounds
// Background shows in full quality, then elements flow in from random directions

const animationCSS = `
/* Background starts full opacity, fades to normal */
body::before {
  animation: bgFadeIn 0.75s ease-out forwards !important;
}

@keyframes bgFadeIn {
  from {
    opacity: 1;
  }
  to {
    opacity: 0.15;
  }
}

/* Hide all elements initially */
body > *:not(script):not(style) {
  opacity: 0;
}

/* Flow-in animations from different directions */
@keyframes flowFromLeft {
  from {
    opacity: 0;
    transform: translateX(-100px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes flowFromRight {
  from {
    opacity: 0;
    transform: translateX(100px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes flowFromTop {
  from {
    opacity: 0;
    transform: translateY(-100px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes flowFromBottom {
  from {
    opacity: 0;
    transform: translateY(100px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.flow-left {
  animation: flowFromLeft 0.75s ease-out forwards;
}

.flow-right {
  animation: flowFromRight 0.75s ease-out forwards;
}

.flow-top {
  animation: flowFromTop 0.75s ease-out forwards;
}

.flow-bottom {
  animation: flowFromBottom 0.75s ease-out forwards;
}
`;

const animationJS = `
// Apply random flow directions to page elements
document.addEventListener('DOMContentLoaded', function() {
  const directions = ['flow-left', 'flow-right', 'flow-top', 'flow-bottom'];
  const elements = document.querySelectorAll('body > *:not(script):not(style)');

  elements.forEach((element, index) => {
    const randomDirection = directions[Math.floor(Math.random() * directions.length)];
    const delay = index * 0.05; // Stagger elements slightly
    element.classList.add(randomDirection);
    element.style.animationDelay = delay + 's';
  });
});
`;

console.log('Animation CSS:');
console.log(animationCSS);
console.log('\nAnimation JS:');
console.log(animationJS);
