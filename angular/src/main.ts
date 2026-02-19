import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app/app.component';

try {
  await bootstrapApplication(AppComponent, {
    providers: [
      // Add any global providers here
    ]
  });
} catch (err) {
  console.error(err);
}