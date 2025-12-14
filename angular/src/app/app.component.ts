import { Component, OnInit, OnDestroy, ElementRef, ViewChild } from '@angular/core';
import { FamilyCommalistComponent } from './family-commalist/family-commalist.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [FamilyCommalistComponent],
  template: `
    <div class="container-fluid">
      <app-family-commalist 
        [initialValue]="initialValue"
        (valueChange)="onValueChange($event)">
      </app-family-commalist>
    </div>
  `,
  styles: [`
    :host {
      display: block;
      width: 100%;
    }
  `]
})
export class AppComponent implements OnInit, OnDestroy {
  initialValue: string = '';
  private targetTextarea: HTMLTextAreaElement | null = null;

  constructor(private elementRef: ElementRef) {}

  ngOnInit() {
    // Get initial value from the textarea
    this.targetTextarea = document.getElementById('family_commalist') as HTMLTextAreaElement;
    if (this.targetTextarea) {
      this.initialValue = this.targetTextarea.value || '';
    }
  }

  ngOnDestroy() {
    // Cleanup
  }

  onValueChange(newValue: string) {
    // Update the original textarea
    if (this.targetTextarea) {
      this.targetTextarea.value = newValue;
      
      // Trigger change event for form validation
      const changeEvent = new Event('change', { bubbles: true });
      this.targetTextarea.dispatchEvent(changeEvent);
      
      // Trigger input event for real-time updates
      const inputEvent = new Event('input', { bubbles: true });
      this.targetTextarea.dispatchEvent(inputEvent);
    }
  }
}