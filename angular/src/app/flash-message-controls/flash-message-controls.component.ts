import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FlashMessageService } from '../services/flash-message.service';

@Component({
  selector: 'app-flash-message-controls',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="flash-controls" *ngIf="activeCount > 0">
      <div class="flash-controls-container">
        <div class="flash-status">
          <span class="flash-count">{{ activeCount }} flash message{{ activeCount !== 1 ? 's' : '' }}</span>
          <span class="flash-paused" *ngIf="pausedCount > 0">({{ pausedCount }} paused)</span>
        </div>
        
        <div class="flash-buttons">
          <button 
            type="button"
            class="btn btn-sm"
            [class.btn-warning]="areAnyPaused"
            [class.btn-secondary]="!areAnyPaused"
            (click)="togglePauseAll()"
            [title]="areAllPaused ? 'Resume all flash messages' : 'Pause all flash messages'">
            <i [class]="areAllPaused ? 'fa fa-play' : 'fa fa-pause'"></i>
            {{ areAllPaused ? 'Resume' : 'Pause' }} All
          </button>
          
          <button 
            type="button"
            class="btn btn-sm btn-danger ms-1"
            (click)="closeAll()"
            title="Close all flash messages">
            <i class="fa fa-times"></i>
            Close All
          </button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .flash-controls {
      position: fixed;
      top: 10px;
      right: 10px;
      z-index: 1055;
      background: rgba(0, 0, 0, 0.8);
      border-radius: 8px;
      padding: 8px 12px;
      color: white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      font-size: 12px;
      max-width: 300px;
    }

    .flash-controls-container {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .flash-status {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .flash-count {
      font-weight: bold;
      color: #fff;
    }

    .flash-paused {
      font-size: 10px;
      color: #ffc107;
    }

    .flash-buttons {
      display: flex;
      gap: 4px;
    }

    .btn-sm {
      font-size: 10px;
      padding: 4px 8px;
      border: none;
    }

    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #5c636a;
    }

    .btn-warning {
      background-color: #ffc107;
      color: #000;
    }

    .btn-warning:hover {
      background-color: #e0a800;
    }

    .btn-danger {
      background-color: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background-color: #c82333;
    }

    .fa {
      margin-right: 3px;
    }

    @media (max-width: 480px) {
      .flash-controls {
        position: relative;
        top: auto;
        right: auto;
        margin: 10px;
        width: calc(100% - 20px);
        max-width: none;
      }
      
      .flash-controls-container {
        justify-content: space-between;
      }
    }
  `]
})
export class FlashMessageControlsComponent implements OnInit, OnDestroy {
  activeCount: number = 0;
  pausedCount: number = 0;
  areAllPaused: boolean = false;
  areAnyPaused: boolean = false;

  private updateInterval?: number;

  constructor(private flashMessageService: FlashMessageService) {}

  ngOnInit() {
    this.flashMessageService.init();
    
    // Start monitoring flash messages
    this.updateInterval = globalThis.window.setInterval(() => {
      this.updateStatus();
    }, 500);

    // Initial update
    setTimeout(() => this.updateStatus(), 100);
  }

  ngOnDestroy() {
    if (this.updateInterval) {
      clearInterval(this.updateInterval);
    }
  }

  private updateStatus() {
    this.activeCount = this.flashMessageService.getActiveCount();
    this.pausedCount = this.flashMessageService.getPausedCount();
    this.areAllPaused = this.flashMessageService.areAllPaused();
    this.areAnyPaused = this.flashMessageService.areAnyPaused();
  }

  togglePauseAll() {
    this.flashMessageService.togglePauseAll();
    // Update status immediately
    setTimeout(() => this.updateStatus(), 100);
  }

  closeAll() {
    this.flashMessageService.closeAll();
    // Update status immediately
    setTimeout(() => this.updateStatus(), 100);
  }
}